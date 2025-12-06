<?php
/**
 * Demo Data Manager
 * Handles XML File Upload & Import.
 * @package Studiofy\Core
 * @version 2.2.19
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
            wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=upload_error'));
            exit;
        }

        $file_tmp = $_FILES['demo_xml_file']['tmp_name'];
        $content = file_get_contents($file_tmp);
        
        // Validation check
        if (strpos($content, '<?xml') === false && strpos($content, '<studiofy_demo>') === false) {
             wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=xml_error'));
             exit;
        }

        $this->process_xml_file($file_tmp);

        // Clear Dashboard Cache
        delete_transient('studiofy_dashboard_stats');

        wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=demo_imported'));
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

        $ids = [
            'customers' => [],
            'projects' => [],
            'tasks' => [],
            'invoices' => [],
            'contracts' => []
        ];

        // 1. Import Customers
        $customer_map = []; 
        if(isset($xml->customers->customer)) {
            foreach ($xml->customers->customer as $c) {
                $xml_id = (int)$c['id']; 
                
                // Construct Address String: Street, City, State, Zip
                $addr_str = implode(', ', array_filter([
                    (string)$c->street,
                    (string)$c->city,
                    (string)$c->state,
                    (string)$c->zip
                ]));

                $wpdb->insert($wpdb->prefix . 'studiofy_customers', [
                    'first_name' => (string)$c->first_name,
                    'last_name'  => (string)$c->last_name,
                    'email'      => (string)$c->email,
                    'phone'      => $enc->encrypt((string)$c->phone),
                    'company'    => (string)$c->company,
                    'address'    => $enc->encrypt($addr_str),
                    'status'     => 'Active',
                    'notes'      => (string)$c->notes,
                    'created_at' => current_time('mysql')
                ]);
                
                $db_id = $wpdb->insert_id;
                $ids['customers'][] = $db_id;
                $customer_map[$xml_id] = $db_id;
            }
        }

        // 2. Import Projects
        $project_map = [];
        if(isset($xml->projects->project)) {
            foreach ($xml->projects->project as $p) {
                $xml_id = (int)$p['id'];
                $cust_xml_id = (int)$p['customer_id'];
                
                $real_cust_id = $customer_map[$cust_xml_id] ?? 0;
                
                if ($real_cust_id) {
                    $wpdb->insert($wpdb->prefix . 'studiofy_projects', [
                        'customer_id' => $real_cust_id,
                        'title'       => (string)$p->title,
                        'status'      => (string)$p->status,
                        'budget'      => (float)$p->budget,
                        'tax_status'  => (string)$p->tax_status,
                        'notes'       => (string)$p->notes,
                        'created_at'  => current_time('mysql')
                    ]);
                    
                    $db_id = $wpdb->insert_id;
                    $ids['projects'][] = $db_id;
                    $project_map[$xml_id] = $db_id;
                }
            }
        }

        // 3. Import Tasks (New for v2.2.19)
        // Tasks are linked to projects. We create a default Milestone for each project first.
        if(isset($xml->tasks->task)) {
            foreach ($xml->tasks->task as $t) {
                $proj_xml_id = (int)$t['project_id'];
                $real_proj_id = $project_map[$proj_xml_id] ?? 0;

                if ($real_proj_id) {
                    // Check/Create Default Milestone
                    $m_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_milestones WHERE project_id = %d LIMIT 1", $real_proj_id));
                    if (!$m_id) {
                        $wpdb->insert($wpdb->prefix.'studiofy_milestones', ['project_id' => $real_proj_id, 'name' => 'General Tasks']);
                        $m_id = $wpdb->insert_id;
                    }

                    $wpdb->insert($wpdb->prefix . 'studiofy_tasks', [
                        'milestone_id' => $m_id,
                        'title'        => (string)$t->title,
                        'priority'     => (string)$t->priority,
                        'description'  => (string)$t->description,
                        'status'       => (string)$t->status,
                        'created_at'   => current_time('mysql')
                    ]);
                    $ids['tasks'][] = $wpdb->insert_id;
                }
            }
        }

        // 4. Import Invoices
        if(isset($xml->invoices->invoice)) {
            foreach ($xml->invoices->invoice as $i) {
                $cust_xml_id = (int)$i['customer_id'];
                $proj_xml_id = (int)$i['project_id'];
                
                $real_cust_id = $customer_map[$cust_xml_id] ?? 0;
                $real_proj_id = $project_map[$proj_xml_id] ?? 0;

                if ($real_cust_id) {
                    $wpdb->insert($wpdb->prefix . 'studiofy_invoices', [
                        'invoice_number' => 'DEMO-' . rand(1000,9999),
                        'customer_id'    => $real_cust_id,
                        'project_id'     => $real_proj_id,
                        'title'          => (string)$i->title,
                        'amount'         => (float)$i->amount,
                        'status'         => (string)$i->status,
                        'issue_date'     => date('Y-m-d'),
                        'due_date'       => date('Y-m-d', strtotime('+30 days')),
                        'currency'       => 'USD',
                        'created_at'     => current_time('mysql')
                    ]);
                    $ids['invoices'][] = $wpdb->insert_id;
                }
            }
        }

        // 5. Import Contracts
        if(isset($xml->contracts->contract)) {
            foreach ($xml->contracts->contract as $con) {
                $cust_xml_id = (int)$con['customer_id'];
                $proj_xml_id = (int)$con['project_id'];

                $real_cust_id = $customer_map[$cust_xml_id] ?? 0;
                $real_proj_id = $project_map[$proj_xml_id] ?? 0;

                if ($real_cust_id) {
                    $wpdb->insert($wpdb->prefix . 'studiofy_contracts', [
                        'title'        => (string)$con->title,
                        'customer_id'  => $real_cust_id,
                        'project_id'   => $real_proj_id,
                        'amount'       => (float)$con->amount,
                        'status'       => (string)$con->status,
                        'body_content' => (string)$con->terms,
                        'start_date'   => date('Y-m-d'),
                        'end_date'     => date('Y-m-d', strtotime('+1 year')),
                        'created_at'   => current_time('mysql')
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
            foreach(['contracts', 'invoices', 'tasks', 'projects', 'customers'] as $key) {
                if (!empty($ids[$key])) {
                    $table = $wpdb->prefix . 'studiofy_' . $key;
                    $in = implode(',', array_map('intval', $ids[$key]));
                    $wpdb->query("DELETE FROM $table WHERE id IN ($in)");
                }
            }
            
            // Cleanup Milestones linked to deleted projects
            $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_milestones WHERE project_id NOT IN (SELECT id FROM {$wpdb->prefix}studiofy_projects)");
        }
        
        delete_option('studiofy_demo_data_ids');
    }
}
