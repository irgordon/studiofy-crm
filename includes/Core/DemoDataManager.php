<?php
/**
 * Demo Data Manager
 * Handles XML File Upload & Import.
 * @package Studiofy\Core
 * @version 2.2.36
 */

declare(strict_types=1);

namespace Studiofy\Core;

use Studiofy\Security\Encryption;

class DemoDataManager {

    public function init(): void {
        add_action('admin_post_studiofy_import_demo', [$this, 'handle_import']);
        add_action('admin_post_studiofy_delete_demo', [$this, 'handle_delete']);
        add_action('admin_post_studiofy_internal_import', [$this, 'handle_internal_import']);
    }

    public function handle_import(): void {
        check_admin_referer('import_demo', 'studiofy_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        if (empty($_FILES['demo_xml_file']) || $_FILES['demo_xml_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=upload_error'));
            exit;
        }

        $file_tmp = $_FILES['demo_xml_file']['tmp_name'];
        $content = file_get_contents($file_tmp);
        if (strpos($content, '<?xml') === false && strpos($content, '<studiofy_demo>') === false) {
             wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=xml_error'));
             exit;
        }

        $this->process_xml_file($file_tmp);
        delete_transient('studiofy_dashboard_stats');

        wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=demo_imported'));
        exit;
    }

    public function handle_internal_import(): void {
        check_admin_referer('internal_import', 'nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $internal_file = STUDIOFY_PATH . 'Studiofy_Demo_data.xml';
        
        if (!file_exists($internal_file)) {
            wp_die('Demo data file not found in plugin directory.');
        }

        $this->process_xml_file($internal_file);
        delete_transient('studiofy_dashboard_stats');

        wp_redirect(admin_url('admin.php?page=studiofy-dashboard&msg=demo_imported'));
        exit;
    }

    public function handle_delete(): void {
        check_admin_referer('delete_demo', 'studiofy_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $this->delete_demo_data();
        delete_transient('studiofy_dashboard_stats');

        wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=demo_deleted'));
        exit;
    }

    private function process_xml_file(string $file_path): void {
        global $wpdb;
        $enc = new Encryption();
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file_path);

        if ($xml === false) {
            wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=xml_error'));
            exit;
        }

        $ids = ['customers' => [], 'projects' => [], 'tasks' => [], 'items' => [], 'invoices' => [], 'contracts' => [], 'galleries' => [], 'files' => []];
        $customer_map = []; 
        $project_map = [];
        $gallery_map = [];

        // 1. Items
        if(isset($xml->items->item)) {
            foreach($xml->items->item as $i) {
                $wpdb->insert($wpdb->prefix.'studiofy_items', [
                    'title'=>(string)$i->title, 'description'=>(string)$i->description, 
                    'rate'=>(float)$i->rate, 'rate_type'=>(string)$i->rate_type, 
                    'default_qty'=>(int)$i->default_qty, 'tax_rate'=>(float)$i->tax_rate
                ]);
                $ids['items'][] = $wpdb->insert_id;
            }
        }

        // 2. Customers
        if(isset($xml->customers->customer)) {
            foreach ($xml->customers->customer as $c) {
                $xml_id = (int)$c['id']; 
                // Fix: Strict string casting to prevent null pointer exceptions
                $addr_str = implode(', ', array_filter([
                    (string)$c->street,
                    (string)$c->city,
                    (string)$c->state,
                    (string)$c->zip
                ]));

                $wpdb->insert($wpdb->prefix . 'studiofy_customers', [
                    'first_name' => (string)$c->first_name, 'last_name' => (string)$c->last_name, 'email' => (string)$c->email,
                    'phone' => $enc->encrypt((string)$c->phone), 'company' => (string)$c->company, 'address' => $enc->encrypt($addr_str),
                    'status' => 'Active', 'notes' => (string)$c->notes, 'created_at' => current_time('mysql')
                ]);
                $db_id = $wpdb->insert_id;
                $ids['customers'][] = $db_id;
                $customer_map[$xml_id] = $db_id;
            }
        }

        // 3. Projects
        if(isset($xml->projects->project)) {
            foreach ($xml->projects->project as $p) {
                $xml_id = (int)$p['id'];
                $real_cust_id = $customer_map[(int)$p['customer_id']] ?? 0;
                if ($real_cust_id) {
                    $wpdb->insert($wpdb->prefix . 'studiofy_projects', [
                        'customer_id' => $real_cust_id, 'title' => (string)$p->title, 'status' => (string)$p->status,
                        'budget' => (float)$p->budget, 'tax_status' => (string)$p->tax_status, 'notes' => (string)$p->notes,
                        'created_at' => current_time('mysql')
                    ]);
                    $db_id = $wpdb->insert_id;
                    $ids['projects'][] = $db_id;
                    $project_map[$xml_id] = $db_id;
                }
            }
        }

        // 4. Tasks
        if(isset($xml->tasks->task)) {
            foreach ($xml->tasks->task as $t) {
                $real_proj_id = $project_map[(int)$t['project_id']] ?? 0;
                if ($real_proj_id) {
                    $m_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_milestones WHERE project_id = %d LIMIT 1", $real_proj_id));
                    if (!$m_id) {
                        $wpdb->insert($wpdb->prefix.'studiofy_milestones', ['project_id' => $real_proj_id, 'name' => 'General Tasks']);
                        $m_id = $wpdb->insert_id;
                    }
                    $wpdb->insert($wpdb->prefix . 'studiofy_tasks', [
                        'milestone_id' => $m_id, 'title' => (string)$t->title, 'priority' => (string)$t->priority,
                        'description' => (string)$t->description, 'status' => (string)$t->status, 'created_at' => current_time('mysql')
                    ]);
                    $ids['tasks'][] = $wpdb->insert_id;
                }
            }
        }

        // 5. Galleries & Files
        if(isset($xml->galleries->gallery)) {
            foreach ($xml->galleries->gallery as $g) {
                $xml_id = (int)$g['id'];
                $real_cust_id = $customer_map[(int)$g['customer_id']] ?? 0;
                $wpdb->insert($wpdb->prefix . 'studiofy_galleries', [
                    'title' => (string)$g->title, 'description' => (string)$g->description, 'customer_id' => $real_cust_id ?: null,
                    'password' => (string)$g->password, 'status' => 'active', 'created_at' => current_time('mysql')
                ]);
                $db_id = $wpdb->insert_id;
                $ids['galleries'][] = $db_id;
                $gallery_map[$xml_id] = $db_id;
                
                $pid = wp_insert_post(['post_title' => (string)$g->title . ' - Demo', 'post_content' => '[studiofy_proof_gallery id="'.$db_id.'"]', 'post_status' => 'publish', 'post_type' => 'page', 'post_password' => (string)$g->password]);
                if($pid) $wpdb->update($wpdb->prefix.'studiofy_galleries', ['wp_page_id' => $pid], ['id' => $db_id]);
            }
        }

        if(isset($xml->gallery_files->file)) {
            foreach ($xml->gallery_files->file as $f) {
                $real_gal_id = $gallery_map[(int)$f['gallery_id']] ?? 0;
                if ($real_gal_id) {
                    $wpdb->insert($wpdb->prefix . 'studiofy_gallery_files', [
                        'gallery_id' => $real_gal_id, 'uploaded_by' => get_current_user_id(), 'file_name' => (string)$f->name, 'file_path' => '', 'file_url' => (string)$f['url'], 'file_type' => 'jpg', 'file_size' => '250KB', 'created_at' => current_time('mysql')
                    ]);
                    $ids['files'][] = $wpdb->insert_id;
                }
            }
        }

        // 6. Invoices
        if(isset($xml->invoices->invoice)) {
            foreach ($xml->invoices->invoice as $inv) {
                $cid = $customer_map[(int)$inv['customer_id']] ?? 0;
                $pid = $project_map[(int)$inv['project_id']] ?? 0;
                
                if ($cid) {
                    $line_items = [];
                    $subtotal = 0;
                    foreach($inv->line_items->line_item as $li) {
                        $qty = (float)$li->qty;
                        $rate = (float)$li->rate;
                        $subtotal += ($qty * $rate);
                        $line_items[] = ['desc' => (string)$li->desc, 'qty' => $qty, 'rate' => $rate];
                    }
                    $tax_rate = (float)$inv->tax_rate;
                    $tax_amt = $subtotal * ($tax_rate / 100);
                    $total = $subtotal + $tax_amt;

                    $wpdb->insert($wpdb->prefix . 'studiofy_invoices', [
                        'invoice_number' => 'DEMO-' . rand(1000,9999), 'customer_id' => $cid, 'project_id' => $pid,
                        'title' => (string)$inv->title, 'amount' => $total, 'tax_amount' => $tax_amt, 'line_items' => json_encode($line_items),
                        'status' => (string)$inv->status, 'issue_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+30 days')),
                        'currency' => 'USD', 'created_at' => current_time('mysql')
                    ]);
                    $ids['invoices'][] = $wpdb->insert_id;
                }
            }
        }

        // 7. Contracts
        if(isset($xml->contracts->contract)) {
            foreach ($xml->contracts->contract as $con) {
                $cid = $customer_map[(int)$con['customer_id']] ?? 0;
                $pid = $project_map[(int)$con['project_id']] ?? 0;
                $content = (string)$con->terms;

                if ($cid) {
                    $cpt_id = wp_insert_post([
                        'post_title' => 'Contract: ' . (string)$con->title,
                        'post_type'  => 'studiofy_doc',
                        'post_status' => 'publish',
                        'post_content' => $content
                    ]);

                    $wpdb->insert($wpdb->prefix . 'studiofy_contracts', [
                        'title' => (string)$con->title, 'customer_id' => $cid, 'project_id' => $pid,
                        'amount' => (float)$con->amount, 'status' => (string)$con->status, 'body_content' => $content,
                        'linked_post_id' => $cpt_id, 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+1 year')), 
                        'created_at' => current_time('mysql')
                    ]);
                    $ids['contracts'][] = $wpdb->insert_id;
                }
            }
        }

        update_option('studiofy_demo_data_ids', $ids);
    }

    public function delete_demo_data(): void {
        global $wpdb;
        $ids = get_option('studiofy_demo_data_ids');
        if ($ids && is_array($ids)) {
            if (!empty($ids['galleries'])) {
                $g_in = implode(',', array_map('intval', $ids['galleries']));
                $pages = $wpdb->get_col("SELECT wp_page_id FROM {$wpdb->prefix}studiofy_galleries WHERE id IN ($g_in) AND wp_page_id IS NOT NULL");
                foreach($pages as $pid) wp_delete_post($pid, true);
            }
            if (!empty($ids['contracts'])) {
                $c_in = implode(',', array_map('intval', $ids['contracts']));
                $posts = $wpdb->get_col("SELECT linked_post_id FROM {$wpdb->prefix}studiofy_contracts WHERE id IN ($c_in) AND linked_post_id IS NOT NULL");
                foreach($posts as $pid) wp_delete_post($pid, true);
            }
            foreach(['gallery_files'=>'files', 'galleries'=>'galleries', 'contracts'=>'contracts', 'invoices'=>'invoices', 'tasks'=>'tasks', 'projects'=>'projects', 'customers'=>'customers', 'items'=>'items'] as $table => $key) {
                if(!empty($ids[$key])) {
                    $tbl = $wpdb->prefix . 'studiofy_' . $table;
                    $in = implode(',', array_map('intval', $ids[$key]));
                    $wpdb->query("DELETE FROM $tbl WHERE id IN ($in)");
                }
            }
            $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_milestones WHERE project_id NOT IN (SELECT id FROM {$wpdb->prefix}studiofy_projects)");
        }
        delete_option('studiofy_demo_data_ids');
    }
}
