<?php
/**
 * Demo Data Manager
 * @package Studiofy\Core
 * @version 2.2.58
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

        $xml_content = file_get_contents($_FILES['demo_xml_file']['tmp_name']);
        if (!$xml_content) {
            wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=xml_error'));
            exit;
        }

        $xml = simplexml_load_string($xml_content);
        if ($xml === false) {
             wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=xml_error'));
             exit;
        }

        $this->process_xml($xml);
        delete_transient('studiofy_dashboard_stats');
        wp_redirect(admin_url('admin.php?page=studiofy-settings&msg=demo_imported'));
        exit;
    }

    public function handle_internal_import(): void {
        check_admin_referer('internal_import', 'nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        // FIX: Use embedded XML content instead of file loading
        $xml_content = $this->get_default_xml_content();
        $xml = simplexml_load_string($xml_content);
        
        if ($xml === false) {
            wp_die('Error parsing internal demo data. Please contact support.');
        }

        $this->process_xml($xml);
        delete_transient('studiofy_dashboard_stats');
        delete_option('studiofy_do_activation_redirect');

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

    private function process_xml($xml): void {
        global $wpdb;
        $enc = new Encryption();
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
                $addr_str = implode(', ', array_filter([(string)$c->street, (string)$c->city, (string)$c->state, (string)$c->zip]));
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
                    $pid = $wpdb->insert_id;
                    $ids['projects'][] = $pid;
                    $project_map[$xml_id] = $pid;
                }
            }
        }

        // 4. Invoices
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
                    $service_fee = 0.00;
                    if (isset($inv->service_fee_enabled) && (string)$inv->service_fee_enabled === 'true') {
                        $service_fee = $subtotal * 0.03;
                    }
                    $total = $subtotal + $tax_amt + $service_fee;

                    $wpdb->insert($wpdb->prefix . 'studiofy_invoices', [
                        'invoice_number' => 'DEMO-' . rand(1000,9999), 'customer_id' => $cid, 'project_id' => $pid,
                        'title' => (string)$inv->title, 'amount' => $total, 'tax_amount' => $tax_amt, 'service_fee' => $service_fee,
                        'line_items' => json_encode($line_items), 'status' => (string)$inv->status, 'payment_method' => (string)($inv->payment_method ?? ''),
                        'issue_date' => date('Y-m-d'), 'due_date' => date('Y-m-d', strtotime('+30 days')), 'currency' => 'USD', 'created_at' => current_time('mysql')
                    ]);
                    $ids['invoices'][] = $wpdb->insert_id;
                }
            }
        }

        // 5. Contracts
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

        // 6. Galleries & Files
        if(isset($xml->galleries->gallery)) {
            foreach ($xml->galleries->gallery as $g) {
                $cid = $customer_map[(int)$g['customer_id']] ?? 0;
                $wpdb->insert($wpdb->prefix . 'studiofy_galleries', [
                    'title' => (string)$g->title, 'description' => (string)$g->description, 'customer_id' => $cid ?: null,
                    'password' => (string)$g->password, 'status' => 'active', 'created_at' => current_time('mysql')
                ]);
                $gid = $wpdb->insert_id;
                $ids['galleries'][] = $gid;
                $gallery_map[(int)$g['id']] = $gid;
                $pid = wp_insert_post(['post_title' => (string)$g->title . ' - Demo', 'post_content' => '[studiofy_proof_gallery id="'.$gid.'"]', 'post_status' => 'publish', 'post_type' => 'studiofy_gal', 'post_password' => (string)$g->password]);
                if($pid) $wpdb->update($wpdb->prefix.'studiofy_galleries', ['wp_page_id' => $pid], ['id' => $gid]);
            }
        }

        if(isset($xml->gallery_files->file)) {
            foreach ($xml->gallery_files->file as $f) {
                $gid = $gallery_map[(int)$f['gallery_id']] ?? 0;
                if ($gid) {
                    $wpdb->insert($wpdb->prefix . 'studiofy_gallery_files', [
                        'gallery_id' => $gid, 'uploaded_by' => get_current_user_id(), 'file_name' => (string)$f->name, 'file_path' => '', 'file_url' => (string)$f['url'], 'file_type' => 'jpg', 'file_size' => '250KB', 'created_at' => current_time('mysql')
                    ]);
                    $ids['files'][] = $wpdb->insert_id;
                }
            }
        }

        // 7. Tasks
        if(isset($xml->tasks->task)) {
            foreach ($xml->tasks->task as $t) {
                $pid = $project_map[(int)$t['project_id']] ?? 0;
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

    /**
     * Returns built-in demo XML content to ensure import works without external file dependencies.
     */
    private function get_default_xml_content(): string {
        return '<?xml version="1.0" encoding="UTF-8"?>
<studiofy_demo>
    <items>
        <item><title>Portrait Session</title><description>1 Hour On-Location</description><rate>200.00</rate><rate_type>Hourly</rate_type><default_qty>1</default_qty><tax_rate>6.0</tax_rate></item>
        <item><title>Wedding Package</title><description>8 Hour Coverage</description><rate>3500.00</rate><rate_type>Fixed</rate_type><default_qty>1</default_qty><tax_rate>6.0</tax_rate></item>
        <item><title>Commercial License</title><description>Usage Rights</description><rate>1000.00</rate><rate_type>Fixed</rate_type><default_qty>1</default_qty><tax_rate>0.0</tax_rate></item>
        <item><title>Event Coverage</title><description>Hourly Rate</description><rate>300.00</rate><rate_type>Hourly</rate_type><default_qty>4</default_qty><tax_rate>6.0</tax_rate></item>
        <item><title>Boudoir Session</title><description>Private Studio</description><rate>500.00</rate><rate_type>Fixed</rate_type><default_qty>1</default_qty><tax_rate>6.0</tax_rate></item>
        <item><title>Lifestyle Branding</title><description>Social Media Pack</description><rate>1200.00</rate><rate_type>Fixed</rate_type><default_qty>1</default_qty><tax_rate>6.0</tax_rate></item>
    </items>
    <customers>
        <customer id="1"><first_name>John</first_name><last_name>Doe</last_name><email>john.doe@demo.com</email><phone>555-0101</phone><company>Doe Corp</company><street>123 Main St</street><city>New York</city><state>NY</state><zip>10001</zip><notes>VIP Client.</notes></customer>
        <customer id="2"><first_name>Jane</first_name><last_name>Smith</last_name><email>jane@demo.com</email><phone>555-0102</phone><company></company><street>200 Oak Ave</street><city>Los Angeles</city><state>CA</state><zip>90001</zip><notes>Wedding inquiry.</notes></customer>
        <customer id="3"><first_name>Mike</first_name><last_name>Ross</last_name><email>mike@demo.com</email><phone>555-0103</phone><company>Pearson Hardman</company><street>300 High St</street><city>Chicago</city><state>IL</state><zip>60601</zip><notes>Corporate headshots.</notes></customer>
        <customer id="4"><first_name>Emily</first_name><last_name>Blunt</last_name><email>emily@demo.com</email><phone>555-0104</phone><company></company><street>400 Elm St</street><city>Austin</city><state>TX</state><zip>73301</zip><notes>Family shoot.</notes></customer>
        <customer id="5"><first_name>Chris</first_name><last_name>Evans</last_name><email>chris@demo.com</email><phone>555-0105</phone><company>Marvel</company><street>500 Pine Rd</street><city>Atlanta</city><state>GA</state><zip>30301</zip><notes>Event coverage.</notes></customer>
        <customer id="6"><first_name>Sarah</first_name><last_name>Connor</last_name><email>sarah@demo.com</email><phone>555-0106</phone><company>Skynet</company><street>600 Cedar Ln</street><city>Seattle</city><state>WA</state><zip>98101</zip><notes>Product launch.</notes></customer>
        <customer id="7"><first_name>David</first_name><last_name>Beckham</last_name><email>david@demo.com</email><phone>555-0107</phone><company>Inter Miami</company><street>700 Palm Dr</street><city>Miami</city><state>FL</state><zip>33101</zip><notes>Sports photography.</notes></customer>
        <customer id="8"><first_name>Jessica</first_name><last_name>Alba</last_name><email>jess@demo.com</email><phone>555-0108</phone><company>Honest Co</company><street>800 Birch Blvd</street><city>Denver</city><state>CO</state><zip>80201</zip><notes>Lifestyle brand.</notes></customer>
        <customer id="9"><first_name>Daniel</first_name><last_name>Craig</last_name><email>bond@demo.com</email><phone>555-0109</phone><company>MI6</company><street>900 Spruce Ct</street><city>London</city><state>UK</state><zip>00700</zip><notes>Confidential.</notes></customer>
        <customer id="10"><first_name>Laura</first_name><last_name>Croft</last_name><email>laura@demo.com</email><phone>555-0110</phone><company>Tomb Raiders</company><street>1000 Maple Way</street><city>Venice</city><state>IT</state><zip>30100</zip><notes>Travel shoot.</notes></customer>
    </customers>
    <projects>
        <project id="1" customer_id="1"><title>Doe Portrait</title><status>in_progress</status><budget>500.00</budget><tax_status>taxed</tax_status><notes>Outdoor.</notes></project>
        <project id="2" customer_id="2"><title>Smith Wedding</title><status>future</status><budget>5000.00</budget><tax_status>taxed</tax_status><notes>Full day.</notes></project>
        <project id="3" customer_id="3"><title>Corporate Headshots</title><status>todo</status><budget>1500.00</budget><tax_status>exempt</tax_status><notes>Office.</notes></project>
        <project id="4" customer_id="4"><title>Family Session</title><status>completed</status><budget>400.00</budget><tax_status>taxed</tax_status><notes>Park location.</notes></project>
        <project id="5" customer_id="5"><title>Movie Premiere</title><status>in_progress</status><budget>3000.00</budget><tax_status>taxed</tax_status><notes>Red carpet.</notes></project>
        <project id="6" customer_id="6"><title>Product Catalog</title><status>todo</status><budget>12000.00</budget><tax_status>exempt</tax_status><notes>Studio.</notes></project>
        <project id="7" customer_id="7"><title>Team Photos</title><status>future</status><budget>2500.00</budget><tax_status>taxed</tax_status><notes>Stadium.</notes></project>
        <project id="8" customer_id="8"><title>Brand Lifestyle</title><status>in_progress</status><budget>4500.00</budget><tax_status>taxed</tax_status><notes>Multiple locations.</notes></project>
        <project id="9" customer_id="9"><title>Secret Location</title><status>completed</status><budget>10000.00</budget><tax_status>exempt</tax_status><notes>Private.</notes></project>
        <project id="10" customer_id="10"><title>Adventure Shoot</title><status>future</status><budget>3500.00</budget><tax_status>taxed</tax_status><notes>Hiking trail.</notes></project>
    </projects>
    <galleries>
        <gallery id="1" customer_id="1"><title>Doe Portraits</title><description>Select your favorites.</description><password>doe123</password></gallery>
        <gallery id="2" customer_id="2"><title>Smith Wedding</title><description>Wedding highlights.</description><password>smith2025</password></gallery>
        <gallery id="3" customer_id="3"><title>Pearson Headshots</title><description>Staff photos.</description><password>ph2025</password></gallery>
        <gallery id="4" customer_id="4"><title>Family Fun</title><description>Park session.</description><password>family</password></gallery>
        <gallery id="5" customer_id="5"><title>Premiere Night</title><description>Red carpet.</description><password>movie</password></gallery>
        <gallery id="6" customer_id="6"><title>Skynet Products</title><description>Catalog shots.</description><password>sky</password></gallery>
        <gallery id="7" customer_id="7"><title>Miami Team</title><description>Action shots.</description><password>miami</password></gallery>
        <gallery id="8" customer_id="8"><title>Honest Lifestyle</title><description>Brand assets.</description><password>honest</password></gallery>
        <gallery id="9" customer_id="9"><title>007 Location</title><description>Confidential.</description><password>bond</password></gallery>
        <gallery id="10" customer_id="10"><title>Tomb Adventure</title><description>Action.</description><password>croft</password></gallery>
    </galleries>
    <gallery_files>
        <file gallery_id="1" url="https://picsum.photos/800/800?random=1"><name>portrait_01.jpg</name></file>
        <file gallery_id="1" url="https://picsum.photos/800/800?random=2"><name>portrait_02.jpg</name></file>
        <file gallery_id="1" url="https://picsum.photos/800/800?random=3"><name>portrait_03.jpg</name></file>
        <file gallery_id="1" url="https://picsum.photos/800/800?random=4"><name>portrait_04.jpg</name></file>
        <file gallery_id="1" url="https://picsum.photos/800/800?random=5"><name>portrait_05.jpg</name></file>
        <file gallery_id="2" url="https://picsum.photos/800/800?random=10"><name>wedding_01.jpg</name></file>
        <file gallery_id="2" url="https://picsum.photos/800/800?random=11"><name>wedding_02.jpg</name></file>
        <file gallery_id="2" url="https://picsum.photos/800/800?random=12"><name>wedding_03.jpg</name></file>
        <file gallery_id="2" url="https://picsum.photos/800/800?random=13"><name>wedding_04.jpg</name></file>
        <file gallery_id="2" url="https://picsum.photos/800/800?random=14"><name>wedding_05.jpg</name></file>
        <file gallery_id="3" url="https://picsum.photos/800/800?random=30"><name>headshot_01.jpg</name></file>
        <file gallery_id="3" url="https://picsum.photos/800/800?random=31"><name>headshot_02.jpg</name></file>
        <file gallery_id="3" url="https://picsum.photos/800/800?random=32"><name>headshot_03.jpg</name></file>
        <file gallery_id="3" url="https://picsum.photos/800/800?random=33"><name>headshot_04.jpg</name></file>
        <file gallery_id="3" url="https://picsum.photos/800/800?random=34"><name>headshot_05.jpg</name></file>
        <file gallery_id="4" url="https://picsum.photos/800/800?random=40"><name>family_01.jpg</name></file>
        <file gallery_id="4" url="https://picsum.photos/800/800?random=41"><name>family_02.jpg</name></file>
        <file gallery_id="4" url="https://picsum.photos/800/800?random=42"><name>family_03.jpg</name></file>
        <file gallery_id="4" url="https://picsum.photos/800/800?random=43"><name>family_04.jpg</name></file>
    </gallery_files>
    <tasks>
        <task project_id="1"><title>Scouting</title><priority>Medium</priority><description>Check park.</description><status>pending</status></task>
        <task project_id="2"><title>Interview</title><priority>High</priority><description>Timeline check.</description><status>pending</status></task>
        <task project_id="6"><title>Retouching</title><priority>Urgent</priority><description>Catalog deadline.</description><status>pending</status></task>
    </tasks>
    <invoices>
        <invoice customer_id="1" project_id="1"><title>Portrait Invoice</title><status>Paid</status><tax_rate>6.0</tax_rate><line_items><line_item><desc>Portrait Session</desc><qty>1</qty><rate>200.00</rate></line_item><line_item><desc>Print Credit</desc><qty>1</qty><rate>50.00</rate></line_item></line_items></invoice>
        <invoice customer_id="2" project_id="2"><title>Wedding Deposit</title><status>Paid</status><tax_rate>6.0</tax_rate><line_items><line_item><desc>Wedding Package</desc><qty>1</qty><rate>3500.00</rate></line_item></line_items></invoice>
        <invoice customer_id="6" project_id="6"><title>Commercial Rights</title><status>Sent</status><tax_rate>0.0</tax_rate><line_items><line_item><desc>Product Catalog</desc><qty>1</qty><rate>12000.00</rate></line_item><line_item><desc>Copyright Release</desc><qty>1</qty><rate>1000.00</rate></line_item></line_items></invoice>
    </invoices>
    <contracts>
        <contract customer_id="1" project_id="1"><title>Portrait Agreement</title><amount>500.00</amount><status>signed</status><terms><![CDATA[<div class="studiofy-contract"><h2>Portrait Agreement</h2><p>Standard portrait terms apply.</p></div>]]></terms></contract>
        <contract customer_id="2" project_id="2"><title>Wedding Contract</title><amount>5000.00</amount><status>draft</status><terms><![CDATA[<div class="studiofy-contract"><h2>Wedding Agreement</h2><p>8 hours coverage.</p></div>]]></terms></contract>
    </contracts>
</studiofy_demo>';
    }
}
