<?php
/**
 * Demo Data Manager
 * @package Studiofy\Core
 * @version 2.3.4
 */

declare(strict_types=1);

namespace Studiofy\Core;

use Studiofy\Security\Encryption;

class DemoDataManager {
    // ... (init, handle_import, handle_internal_import, handle_delete same as v2.3.0) ...
    public function init(): void { add_action('admin_post_studiofy_import_demo', [$this, 'handle_import']); add_action('admin_post_studiofy_delete_demo', [$this, 'handle_delete']); add_action('admin_post_studiofy_internal_import', [$this, 'handle_internal_import']); }
    public function handle_import(): void { check_admin_referer('import_demo', 'studiofy_nonce'); if (!current_user_can('manage_options')) wp_die('Unauthorized'); if (empty($_FILES['demo_xml_file']) || $_FILES['demo_xml_file']['error'] !== UPLOAD_ERR_OK) { wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=upload_error')); exit; } $file_tmp = $_FILES['demo_xml_file']['tmp_name']; $xml_content = file_get_contents($file_tmp); if (!$xml_content) { wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=xml_error')); exit; } $xml = simplexml_load_string($xml_content); if ($xml === false) { wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=xml_error')); exit; } $this->process_xml($xml); delete_transient('studiofy_dashboard_stats'); wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=demo_imported')); exit; }
    public function handle_internal_import(): void { check_admin_referer('internal_import', 'nonce'); if (!current_user_can('manage_options')) wp_die('Unauthorized'); $xml_content = $this->get_default_xml_content(); $xml = simplexml_load_string($xml_content); if ($xml === false) wp_die('Error parsing internal demo data.'); $this->process_xml($xml); delete_transient('studiofy_dashboard_stats'); delete_option('studiofy_do_activation_redirect'); wp_redirect(admin_url('admin.php?page=studiofy-dashboard&msg=demo_imported')); exit; }
    public function handle_delete(): void { check_admin_referer('delete_demo', 'studiofy_nonce'); if (!current_user_can('manage_options')) wp_die('Unauthorized'); $this->delete_demo_data(); delete_transient('studiofy_dashboard_stats'); wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=demo_deleted')); exit; }

    // ... (process_xml, delete_demo_data same as v2.3.0) ...
    private function process_xml($xml): void { global $wpdb; $enc = new Encryption(); $ids = ['customers'=>[],'projects'=>[],'tasks'=>[],'items'=>[],'invoices'=>[],'galleries'=>[],'files'=>[]]; $customer_map = []; $project_map = []; $gallery_map = []; if(isset($xml->items->item)) { foreach($xml->items->item as $i) { $wpdb->insert($wpdb->prefix.'studiofy_items', ['title'=>(string)$i->title, 'description'=>(string)$i->description, 'rate'=>(float)$i->rate, 'rate_type'=>(string)$i->rate_type, 'default_qty'=>(int)$i->default_qty, 'tax_rate'=>(float)$i->tax_rate]); $ids['items'][] = $wpdb->insert_id; } } if(isset($xml->customers->customer)) { foreach ($xml->customers->customer as $c) { $xml_id = (int)$c['id']; $addr_str = implode(', ', array_filter([(string)$c->street, (string)$c->city, (string)$c->state, (string)$c->zip])); $wpdb->insert($wpdb->prefix . 'studiofy_customers', ['first_name' => (string)$c->first_name, 'last_name' => (string)$c->last_name, 'email' => (string)$c->email, 'phone' => $enc->encrypt((string)$c->phone), 'company' => (string)$c->company, 'address' => $enc->encrypt($addr_str), 'status' => 'Active', 'notes' => (string)$c->notes, 'created_at' => current_time('mysql')]); $db_id = $wpdb->insert_id; $ids['customers'][] = $db_id; $customer_map[$xml_id] = $db_id; } } if(isset($xml->projects->project)) { foreach ($xml->projects->project as $p) { $xml_id = (int)$p['id']; $real_cust_id = $customer_map[(int)$p['customer_id']] ?? 0; if ($real_cust_id) { $wpdb->insert($wpdb->prefix . 'studiofy_projects', ['customer_id' => $real_cust_id, 'title' => (string)$p->title, 'status' => (string)$p->status, 'budget' => (float)$p->budget, 'tax_status' => (string)$p->tax_status, 'notes' => (string)$p->notes, 'created_at' => current_time('mysql')]); $pid = $wpdb->insert_id; $ids['projects'][] = $pid; $project_map[$xml_id] = $pid; } } } if(isset($xml->billing->record)) { foreach ($xml->billing->record as $rec) { $cid = $customer_map[(int)$rec['customer_id']] ?? 0; $pid = $project_map[(int)$rec['project_id']] ?? 0; if ($cid) { $line_items = []; $subtotal = 0; foreach($rec->line_items->line_item as $li) { $qty = (float)$li->qty; $rate = (float)$li->rate; $subtotal += ($qty * $rate); $line_items[] = ['desc' => (string)$li->desc, 'qty' => $qty, 'rate' => $rate]; } $tax_rate = (float)$rec->tax_rate; $tax_amt = $subtotal * ($tax_rate / 100); $service_fee = (float)($rec->service_fee ?? 0); $total = $subtotal + $tax_amt + $service_fee; $methods = []; if(isset($rec->payment_methods)) { foreach($rec->payment_methods->method as $m) $methods[] = (string)$m; } $contract_body = (string)$rec->contract_body; $wpdb->insert($wpdb->prefix . 'studiofy_invoices', ['invoice_number' => 'INV-' . rand(10000,99999), 'customer_id' => $cid, 'project_id' => $pid, 'title' => (string)$rec->title, 'service_type' => (string)$rec->service_type, 'contract_body' => $contract_body, 'contract_status' => (string)$rec->contract_status, 'amount' => $total, 'tax_amount' => $tax_amt, 'service_fee' => $service_fee, 'deposit_amount' => (float)($rec->deposit_amount ?? 0), 'payment_methods' => json_encode($methods), 'line_items' => json_encode($line_items), 'status' => (string)$rec->status, 'issue_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+14 days')), 'currency' => 'USD', 'created_at' => current_time('mysql')]); $ids['invoices'][] = $wpdb->insert_id; } } } if(isset($xml->galleries->gallery)) { foreach ($xml->galleries->gallery as $g) { $cid = $customer_map[(int)$g['customer_id']] ?? 0; $wpdb->insert($wpdb->prefix . 'studiofy_galleries', ['title' => (string)$g->title, 'description' => (string)$g->description, 'customer_id' => $cid ?: null, 'password' => (string)$g->password, 'status' => 'active', 'created_at' => current_time('mysql')]); $gid = $wpdb->insert_id; $ids['galleries'][] = $gid; $gallery_map[(int)$g['id']] = $gid; $pid = wp_insert_post(['post_title' => (string)$g->title . ' - Demo', 'post_content' => '[studiofy_proof_gallery id="'.$gid.'"]', 'post_status' => 'publish', 'post_type' => 'studiofy_gal', 'post_password' => (string)$g->password]); if($pid) $wpdb->update($wpdb->prefix.'studiofy_galleries', ['wp_page_id' => $pid], ['id' => $gid]); } } if(isset($xml->gallery_files->file)) { foreach ($xml->gallery_files->file as $f) { $gid = $gallery_map[(int)$f['gallery_id']] ?? 0; if ($gid) { $wpdb->insert($wpdb->prefix . 'studiofy_gallery_files', ['gallery_id' => $gid, 'uploaded_by' => get_current_user_id(), 'file_name' => (string)$f->name, 'file_path' => '', 'file_url' => (string)$f['url'], 'file_type' => 'jpg', 'file_size' => '250KB', 'created_at' => current_time('mysql')]); $ids['files'][] = $wpdb->insert_id; } } } if(isset($xml->tasks->task)) { foreach ($xml->tasks->task as $t) { $pid = $project_map[(int)$t['project_id']] ?? 0; if ($pid) { $mid = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_milestones WHERE project_id = %d LIMIT 1", $pid)); if (!$mid) { $wpdb->insert($wpdb->prefix.'studiofy_milestones', ['project_id'=>$pid,'name'=>'General']); $mid=$wpdb->insert_id; } $wpdb->insert($wpdb->prefix.'studiofy_tasks', ['milestone_id'=>$mid, 'title'=>(string)$t->title, 'priority'=>(string)$t->priority, 'description'=>(string)$t->description, 'status'=>(string)$t->status, 'created_at'=>current_time('mysql')]); $ids['tasks'][] = $wpdb->insert_id; } } } update_option('studiofy_demo_data_ids', $ids); }
    public function delete_demo_data(): void { global $wpdb; $ids = get_option('studiofy_demo_data_ids'); if ($ids) { if (!empty($ids['galleries'])) { $g_in = implode(',', array_map('intval', $ids['galleries'])); $pages = $wpdb->get_col("SELECT wp_page_id FROM {$wpdb->prefix}studiofy_galleries WHERE id IN ($g_in) AND wp_page_id IS NOT NULL"); foreach($pages as $pid) wp_delete_post($pid, true); } foreach(['gallery_files'=>'files', 'galleries'=>'galleries', 'invoices'=>'invoices', 'tasks'=>'tasks', 'projects'=>'projects', 'customers'=>'customers', 'items'=>'items'] as $table => $key) { if(!empty($ids[$key])) { $tbl = $wpdb->prefix . 'studiofy_' . $table; $in = implode(',', array_map('intval', $ids[$key])); $wpdb->query("DELETE FROM $tbl WHERE id IN ($in)"); } } $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_milestones WHERE project_id NOT IN (SELECT id FROM {$wpdb->prefix}studiofy_projects)"); } delete_option('studiofy_demo_data_ids'); }

    private function get_default_xml_content(): string {
        $boilerplate = htmlspecialchars('<h3>1. Contact Information</h3><p>[Contact Info Here]</p><h3>2. Scope of Work</h3><p>Assignment details...</p><h3>3. Payment Terms</h3><p>Deposit and Due dates...</p><h3>4. Usage Rights</h3><p>License details...</p><h3>5. Delivery Timeline</h3><p>Delivery schedule...</p><h3>6. Cancellation Policy</h3><p>Terms...</p><h3>7. Releases</h3><p>Model/Property releases...</p><h3>8. Liability</h3><p>Indemnity clause...</p><h3>9. Force Majeure</h3><p>Unforeseen events...</p><h3>10. Signatures</h3><p>Sign below...</p>');
        
        return '<?xml version="1.0" encoding="UTF-8"?>
<studiofy_demo>
    <items>
        <item><title>Portrait Session</title><description>1 Hour On-Location</description><rate>250.00</rate><rate_type>Fixed</rate_type><default_qty>1</default_qty><tax_rate>6.0</tax_rate></item>
        <item><title>Wedding Coverage</title><description>8 Hours</description><rate>4000.00</rate><rate_type>Fixed</rate_type><default_qty>1</default_qty><tax_rate>6.0</tax_rate></item>
    </items>
    <customers>
        <customer id="1"><first_name>Alice</first_name><last_name>Vaughn</last_name><email>alice@vaughn.design</email><phone>555-0101</phone><company>Vaughn Interiors</company><street>123 Design</street><city>New York</city><state>NY</state><zip>10001</zip><notes>Commercial.</notes></customer>
        <customer id="2"><first_name>Marcus</first_name><last_name>Chen</last_name><email>marcus@tech.io</email><phone>555-0102</phone><company>StartUp</company><street>456 Valley</street><city>SF</city><state>CA</state><zip>94103</zip><notes>Headshots.</notes></customer>
    </customers>
    <projects>
        <project id="1" customer_id="1"><title>Vaughn Showroom</title><status>in_progress</status><budget>2500.00</budget><tax_status>taxed</tax_status><notes>Interiors.</notes></project>
        <project id="2" customer_id="2"><title>StartUp Team</title><status>completed</status><budget>1200.00</budget><tax_status>taxed</tax_status><notes>Headshots.</notes></project>
    </projects>
    <billing>
        <record customer_id="1" project_id="1">
            <title>Commercial Agreement</title>
            <service_type>Commercial</service_type>
            <contract_status>Signed</contract_status>
            <status>Paid</status>
            <tax_rate>6.0</tax_rate>
            <service_fee>0.00</service_fee>
            <payment_methods><method>Bank Transfer</method></payment_methods>
            <contract_body>' . $boilerplate . '</contract_body>
            <line_items><line_item><desc>Interior Photography Day Rate</desc><qty>2</qty><rate>1250.00</rate></line_item></line_items>
        </record>
        <record customer_id="2" project_id="2">
            <title>Headshot Invoice</title>
            <service_type>Portrait</service_type>
            <contract_status>Unsigned</contract_status>
            <status>Sent</status>
            <tax_rate>6.0</tax_rate>
            <service_fee>0.00</service_fee>
            <payment_methods><method>Credit Card</method></payment_methods>
            <contract_body>' . $boilerplate . '</contract_body>
            <line_items><line_item><desc>Team Headshots</desc><qty>10</qty><rate>120.00</rate></line_item></line_items>
        </record>
    </billing>
    <galleries>
        <gallery id="1" customer_id="1"><title>Vaughn Interiors</title><description>Select favs.</description><password>vaughn</password></gallery>
    </galleries>
    <gallery_files>
        <file gallery_id="1" url="https://picsum.photos/800/800?random=1"><name>interior_01.jpg</name></file>
    </gallery_files>
    <tasks>
        <task project_id="1"><title>Scout Location</title><priority>Medium</priority><status>completed</status></task>
    </tasks>
</studiofy_demo>';
    }
}
