<?php
/**
 * Demo Data Manager
 * @package Studiofy\Core
 * @version 2.2.31
 */

declare(strict_types=1);

namespace Studiofy\Core;

use Studiofy\Security\Encryption;

class DemoDataManager {

    public function init(): void {
        add_action('admin_post_studiofy_import_demo', [$this, 'handle_import']);
        add_action('admin_post_studiofy_delete_demo', [$this, 'handle_delete']);
    }

    public function handle_import(): void {
        check_admin_referer('import_demo', 'studiofy_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        if (empty($_FILES['demo_xml_file']) || $_FILES['demo_xml_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=upload_error')); exit;
        }

        $xml = simplexml_load_file($_FILES['demo_xml_file']['tmp_name']);
        if ($xml === false) { wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=xml_error')); exit; }

        $this->process_xml_file($xml);
        delete_transient('studiofy_dashboard_stats');
        wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=demo_imported')); exit;
    }

    public function handle_delete(): void {
        check_admin_referer('delete_demo', 'studiofy_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $this->delete_demo_data();
        wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=demo_deleted')); exit;
    }

    private function process_xml_file($xml): void {
        global $wpdb;
        $enc = new Encryption();
        $ids = ['customers'=>[], 'projects'=>[], 'tasks'=>[], 'items'=>[], 'invoices'=>[], 'contracts'=>[], 'galleries'=>[], 'files'=>[]];
        $map = ['cust'=>[], 'proj'=>[]];

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
                $addr = implode(', ', array_filter([(string)$c->street, (string)$c->city, (string)$c->state, (string)$c->zip]));
                $wpdb->insert($wpdb->prefix . 'studiofy_customers', [
                    'first_name' => (string)$c->first_name, 'last_name' => (string)$c->last_name, 'email' => (string)$c->email,
                    'phone' => $enc->encrypt((string)$c->phone), 'company' => (string)$c->company, 'address' => $enc->encrypt($addr),
                    'status' => 'Active', 'notes' => (string)$c->notes, 'created_at' => current_time('mysql')
                ]);
                $dbid = $wpdb->insert_id;
                $ids['customers'][] = $dbid;
                $map['cust'][(int)$c['id']] = $dbid;
            }
        }

        // 3. Projects
        if(isset($xml->projects->project)) {
            foreach ($xml->projects->project as $p) {
                $cid = $map['cust'][(int)$p['customer_id']] ?? 0;
                if ($cid) {
                    $wpdb->insert($wpdb->prefix . 'studiofy_projects', [
                        'customer_id' => $cid, 'title' => (string)$p->title, 'status' => (string)$p->status,
                        'budget' => (float)$p->budget, 'tax_status' => (string)$p->tax_status, 'notes' => (string)$p->notes, 'created_at' => current_time('mysql')
                    ]);
                    $pid = $wpdb->insert_id;
                    $ids['projects'][] = $pid;
                    $map['proj'][(int)$p['id']] = $pid;
                }
            }
        }

        // 4. Invoices (With complex math)
        if(isset($xml->invoices->invoice)) {
            foreach ($xml->invoices->invoice as $inv) {
                $cid = $map['cust'][(int)$inv['customer_id']] ?? 0;
                $pid = $map['proj'][(int)$inv['project_id']] ?? 0;
                
                if ($cid) {
                    $line_items = [];
                    $subtotal = 0;
                    
                    // Parse line items
                    foreach($inv->line_items->line_item as $li) {
                        $qty = (float)$li->qty;
                        $rate = (float)$li->rate;
                        $amt = $qty * $rate;
                        $subtotal += $amt;
                        $line_items[] = [
                            'desc' => (string)$li->desc,
                            'qty' => $qty,
                            'rate' => $rate
                        ];
                    }
                    
                    $tax_rate = (float)$inv->tax_rate; // 6.0
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

        // 5. Tasks (Basic)
        if(isset($xml->tasks->task)) {
            foreach ($xml->tasks->task as $t) {
                $pid = $map['proj'][(int)$t['project_id']] ?? 0;
                if ($pid) {
                    $mid = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_milestones WHERE project_id = %d LIMIT 1", $pid));
                    if (!$mid) { $wpdb->insert($wpdb->prefix.'studiofy_milestones', ['project_id'=>$pid,'name'=>'General']); $mid=$wpdb->insert_id; }
                    $wpdb->insert($wpdb->prefix.'studiofy_tasks', ['milestone_id'=>$mid, 'title'=>(string)$t->title, 'priority'=>(string)$t->priority, 'description'=>(string)$t->description, 'status'=>(string)$t->status, 'created_at'=>current_time('mysql')]);
                    $ids['tasks'][] = $wpdb->insert_id;
                }
            }
        }

        update_option('studiofy_demo_data_ids', $ids);
    }

    public function delete_demo_data(): void {
        global $wpdb;
        $ids = get_option('studiofy_demo_data_ids');
        if ($ids) {
            foreach(['items', 'invoices', 'tasks', 'projects', 'customers'] as $k) {
                if(!empty($ids[$k])) {
                    $tbl = $wpdb->prefix . 'studiofy_' . $k;
                    $in = implode(',', array_map('intval', $ids[$k]));
                    $wpdb->query("DELETE FROM $tbl WHERE id IN ($in)");
                }
            }
            $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_milestones WHERE project_id NOT IN (SELECT id FROM {$wpdb->prefix}studiofy_projects)");
        }
        delete_option('studiofy_demo_data_ids');
    }
}
