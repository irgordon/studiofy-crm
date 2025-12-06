<?php
/**
 * Demo Data Manager
 * Handles XML Import/Delete for Demo Content.
 * @package Studiofy\Core
 * @version 2.2.4
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

        $this->import_xml();

        wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=demo_imported'));
        exit;
    }

    public function handle_delete(): void {
        check_admin_referer('delete_demo', 'studiofy_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $this->delete_demo_data();

        wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=demo_deleted'));
        exit;
    }

    private function import_xml(): void {
        global $wpdb;
        $enc = new Encryption();
        $xml_string = $this->get_demo_xml();
        $xml = simplexml_load_string($xml_string);

        $ids = [
            'customers' => [],
            'projects' => [],
            'invoices' => [],
            'contracts' => []
        ];

        // 1. Import Customers (10)
        $customer_map = []; // internal xml id -> db id
        foreach ($xml->customers->customer as $c) {
            $wpdb->insert($wpdb->prefix . 'studiofy_customers', [
                'first_name' => (string)$c->first_name,
                'last_name'  => (string)$c->last_name,
                'email'      => (string)$c->email,
                'phone'      => $enc->encrypt((string)$c->phone),
                'company'    => (string)$c->company,
                'address'    => $enc->encrypt((string)$c->address),
                'status'     => 'Active',
                'notes'      => (string)$c->notes
            ]);
            $db_id = $wpdb->insert_id;
            $ids['customers'][] = $db_id;
            $customer_map[(int)$c['id']] = $db_id;
        }

        // 2. Import Projects (6)
        $project_map = [];
        foreach ($xml->projects->project as $p) {
            $cust_xml_id = (int)$p->customer_id;
            $real_cust_id = $customer_map[$cust_xml_id] ?? 0;
            
            if ($real_cust_id) {
                $wpdb->insert($wpdb->prefix . 'studiofy_projects', [
                    'customer_id' => $real_cust_id,
                    'title'       => (string)$p->title,
                    'status'      => (string)$p->status,
                    'budget'      => (float)$p->budget,
                    'tax_status'  => (string)$p->tax_status,
                    'notes'       => (string)$p->notes
                ]);
                $db_id = $wpdb->insert_id;
                $ids['projects'][] = $db_id;
                $project_map[(int)$p['id']] = $db_id;
            }
        }

        // 3. Import Invoices (6)
        foreach ($xml->invoices->invoice as $i) {
            $cust_xml_id = (int)$i->customer_id;
            $proj_xml_id = (int)$i->project_id;
            
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
                    'currency'       => 'USD'
                ]);
                $ids['invoices'][] = $wpdb->insert_id;
            }
        }

        // 4. Import Contracts (6)
        foreach ($xml->contracts->contract as $con) {
            $cust_xml_id = (int)$con->customer_id;
            $proj_xml_id = (int)$con->project_id;

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
                    'end_date'     => date('Y-m-d', strtotime('+1 year'))
                ]);
                $ids['contracts'][] = $wpdb->insert_id;
            }
        }

        update_option('studiofy_demo_data_ids', $ids);
    }

    private function delete_demo_data(): void {
        global $wpdb;
        $ids = get_option('studiofy_demo_data_ids');

        if ($ids && is_array($ids)) {
            // Delete Contracts
            if (!empty($ids['contracts'])) {
                $in = implode(',', array_map('intval', $ids['contracts']));
                $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_contracts WHERE id IN ($in)");
            }
            // Delete Invoices
            if (!empty($ids['invoices'])) {
                $in = implode(',', array_map('intval', $ids['invoices']));
                $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_invoices WHERE id IN ($in)");
            }
            // Delete Projects
            if (!empty($ids['projects'])) {
                $in = implode(',', array_map('intval', $ids['projects']));
                $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_projects WHERE id IN ($in)");
            }
            // Delete Customers
            if (!empty($ids['customers'])) {
                $in = implode(',', array_map('intval', $ids['customers']));
                $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_customers WHERE id IN ($in)");
            }
        }
        
        delete_option('studiofy_demo_data_ids');
    }

    private function get_demo_xml(): string {
        return <<<XML
<studiofy_demo>
    <customers>
        <customer id="1"><first_name>John</first_name><last_name>Doe</last_name><email>john.doe@demo.com</email><phone>555-0101</phone><company>Doe Corp</company><address>123 Main St</address><notes>Interested in portrait session.</notes></customer>
        <customer id="2"><first_name>Jane</first_name><last_name>Smith</last_name><email>jane.smith@demo.com</email><phone>555-0102</phone><company></company><address>456 Oak Ave</address><notes>Wedding inquiry.</notes></customer>
        <customer id="3"><first_name>Michael</first_name><last_name>Johnson</last_name><email>mike.j@demo.com</email><phone>555-0103</phone><company>MJ Designs</company><address>789 Pine Ln</address><notes>Commercial shoot.</notes></customer>
        <customer id="4"><first_name>Emily</first_name><last_name>Davis</last_name><email>emily.d@demo.com</email><phone>555-0104</phone><company></company><address>321 Elm St</address><notes>Boudoir session.</notes></customer>
        <customer id="5"><first_name>Chris</first_name><last_name>Brown</last_name><email>chris.b@demo.com</email><phone>555-0105</phone><company></company><address>654 Maple Dr</address><notes>Lifestyle photography.</notes></customer>
        <customer id="6"><first_name>Sarah</first_name><last_name>Wilson</last_name><email>sarah.w@demo.com</email><phone>555-0106</phone><company>Wilson Events</company><address>987 Cedar Rd</address><notes>Event coverage.</notes></customer>
        <customer id="7"><first_name>David</first_name><last_name>Miller</last_name><email>david.m@demo.com</email><phone>555-0107</phone><company></company><address>147 Birch Blvd</address><notes>Family portrait.</notes></customer>
        <customer id="8"><first_name>Jessica</first_name><last_name>Taylor</last_name><email>jess.t@demo.com</email><phone>555-0108</phone><company></company><address>258 Willow Way</address><notes>Headshots.</notes></customer>
        <customer id="9"><first_name>Daniel</first_name><last_name>Anderson</last_name><email>dan.a@demo.com</email><phone>555-0109</phone><company>TechStart</company><address>369 Spruce Ct</address><notes>Product photography.</notes></customer>
        <customer id="10"><first_name>Laura</first_name><last_name>Thomas</last_name><email>laura.t@demo.com</email><phone>555-0110</phone><company></company><address>741 Aspen Pl</address><notes>Engagement shoot.</notes></customer>
    </customers>
    <projects>
        <project id="1" customer_id="1"><title>Doe Portrait Session</title><status>in_progress</status><budget>400.00</budget><tax_status>taxed</tax_status><notes>Outdoor location.</notes></project>
        <project id="2" customer_id="2"><title>Smith Wedding</title><status>future</status><budget>5000.00</budget><tax_status>taxed</tax_status><notes>Full day coverage.</notes></project>
        <project id="3" customer_id="3"><title>Commercial Product Launch</title><status>todo</status><budget>10000.00</budget><tax_status>exempt</tax_status><notes>Studio shoot.</notes></project>
        <project id="4" customer_id="4"><title>Boudoir Collection</title><status>in_progress</status><budget>1500.00</budget><tax_status>taxed</tax_status><notes>Private studio.</notes></project>
        <project id="5" customer_id="5"><title>Lifestyle Brand</title><status>todo</status><budget>2500.00</budget><tax_status>taxed</tax_status><notes>Urban setting.</notes></project>
        <project id="6" customer_id="9"><title>Tech Headshots</title><status>future</status><budget>800.00</budget><tax_status>exempt</tax_status><notes>Office location.</notes></project>
    </projects>
    <invoices>
        <invoice customer_id="1" project_id="1"><title>Deposit - Portrait</title><amount>200.00</amount><status>Paid</status></invoice>
        <invoice customer_id="2" project_id="2"><title>Wedding Package 1</title><amount>5000.00</amount><status>Sent</status></invoice>
        <invoice customer_id="3" project_id="3"><title>Commercial Retainer</title><amount>5000.00</amount><status>Draft</status></invoice>
        <invoice customer_id="4" project_id="4"><title>Boudoir Session Fee</title><amount>1500.00</amount><status>Paid</status></invoice>
        <invoice customer_id="3" project_id="3"><title>Commercial Final (Tax Exempt)</title><amount>5000.00</amount><status>Draft</status></invoice>
        <invoice customer_id="9" project_id="6"><title>Headshot Session (Tax Exempt)</title><amount>800.00</amount><status>Sent</status></invoice>
    </invoices>
    <contracts>
        <contract customer_id="1" project_id="1"><title>Portrait Agreement</title><amount>400.00</amount><status>signed</status><terms>Standard portrait terms.</terms></contract>
        <contract customer_id="2" project_id="2"><title>Wedding Contract</title><amount>5000.00</amount><status>draft</status><terms>Wedding photography exclusive rights.</terms></contract>
        <contract customer_id="3" project_id="3"><title>Commercial License</title><amount>10000.00</amount><status>active</status><terms>Commercial usage rights granted.</terms></contract>
        <contract customer_id="4" project_id="4"><title>Privacy Agreement</title><amount>1500.00</amount><status>signed</status><terms>Strict privacy and non-disclosure.</terms></contract>
        <contract customer_id="5" project_id="5"><title>Model Release</title><amount>0.00</amount><status>signed</status><terms>Standard model release form.</terms></contract>
        <contract customer_id="9" project_id="6"><title>Corporate Service Agreement</title><amount>800.00</amount><status>draft</status><terms>Corporate headshot terms.</terms></contract>
    </contracts>
</studiofy_demo>
XML;
    }
}
