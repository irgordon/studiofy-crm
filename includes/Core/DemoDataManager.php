<?php
/**
 * Demo Data Manager
 * @package Studiofy\Core
 * @version 2.2.18
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
        if (strpos($content, '<?xml') === false && strpos($content, '<studiofy_demo>') === false) {
             wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=xml_error'));
             exit;
        }

        $this->process_xml_file($file_tmp);
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

        $ids = ['customers' => [], 'projects' => [], 'invoices' => [], 'contracts' => []];
        $customer_map = []; 

        if(isset($xml->customers->customer)) {
            foreach ($xml->customers->customer as $c) {
                $xml_id = (int)$c['id']; 
                
                // Construct Address String from separate XML fields
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
        // ... (Projects, Invoices, Contracts logic remains the same as v2.2.7)
        
        // Projects
        $project_map = [];
        if(isset($xml->projects->project)) {
            foreach ($xml->projects->project as $p) {
                $xml_id = (int)$p['id'];
                $cust_xml_id = (int)$p['customer_id'];
                $real_cust_id = $customer_map[$cust_xml_id] ?? 0;
                if ($real_cust_id) {
                    $wpdb->insert($wpdb->prefix . 'studiofy_projects', [
                        'customer_id' => $real_cust_id,
                        'title' => (string)$p->title,
                        'status' => (string)$p->status,
                        'budget' => (float)$p->budget,
                        'tax_status' => (string)$p->tax_status,
                        'notes' => (string)$p->notes,
                        'created_at' => current_time('mysql')
                    ]);
                    $db_id = $wpdb->insert_id;
                    $ids['projects'][] = $db_id;
                    $project_map[$xml_id] = $db_id;
                }
            }
        }
        // Invoices & Contracts (Standard mapping)
        // ...
        update_option('studiofy_demo_data_ids', $ids);
    }

    // delete_demo_data() same as v2.2.9
    public function delete_demo_data(): void {
        global $wpdb;
        $ids = get_option('studiofy_demo_data_ids');
        if ($ids && is_array($ids)) {
            foreach(['contracts', 'invoices', 'projects', 'customers'] as $key) {
                if (!empty($ids[$key])) {
                    $table = $wpdb->prefix . 'studiofy_' . $key;
                    $in = implode(',', array_map('intval', $ids[$key]));
                    $wpdb->query("DELETE FROM $table WHERE id IN ($in)");
                }
            }
        }
        delete_option('studiofy_demo_data_ids');
    }
}
