<?php
declare( strict_types=1 );
class Studiofy_Admin {
	private string $name; private string $ver;
	public function __construct(string $n, string $v) { $this->name=$n; $this->ver=$v; }
	public function enqueue_scripts(string $h): void {
		global $post;
		if($post && 'studiofy_gallery'===$post->post_type) wp_enqueue_media();
		if(strpos($h,'studiofy')===false && strpos($h,'post.php')===false) return;
		
		wp_enqueue_style($this->name, STUDIOFY_URL.'admin/css/studiofy-admin.css', [], $this->ver);
		wp_enqueue_script($this->name, STUDIOFY_URL.'admin/js/studiofy-admin.js', ['jquery'], $this->ver, true);
		
		if(isset($_GET['post_type']) && $_GET['post_type']=='studiofy_project' && isset($_GET['view'])) {
			wp_enqueue_style('studiofy-kanban', STUDIOFY_URL.'admin/css/studiofy-kanban.css', [], $this->ver);
			wp_enqueue_script('studiofy-kanban-js', STUDIOFY_URL.'admin/js/studiofy-kanban.js', ['jquery-ui-sortable'], $this->ver, true);
			wp_localize_script('studiofy-kanban-js','studiofy_vars',['ajax_url'=>admin_url('admin-ajax.php'),'kanban_nonce'=>wp_create_nonce('studiofy_kanban_nonce')]);
		}
		if($post && 'studiofy_contract'===$post->post_type) wp_enqueue_script('studiofy-c-b', STUDIOFY_URL.'admin/js/studiofy-contract-builder.js', ['jquery-ui-sortable'], $this->ver, true);
	}
	public function add_plugin_admin_menu(): void {
		add_menu_page('Studiofy', 'Studiofy', 'manage_options', 'studiofy-dashboard', [$this,'dash'], 'dashicons-camera', 6);
		add_submenu_page('studiofy-dashboard', 'Projects', 'Projects', 'edit_posts', 'studiofy-projects', [$this,'route_projects']);
		add_submenu_page('studiofy-dashboard', 'Settings', 'Settings', 'manage_options', 'studiofy-settings', [$this,'settings']);
	}
	public function dash(): void { echo '<div class="wrap"><h1>Studiofy Everest</h1><p>Welcome to v3.0.</p></div>'; }
	public function settings(): void { require_once STUDIOFY_PATH.'admin/class-studiofy-settings.php'; (new Studiofy_Settings())->render_page(); }
	public function route_projects(): void {
		if(isset($_GET['view']) && $_GET['view']=='kanban') {
			require_once STUDIOFY_PATH.'includes/modules/class-studiofy-kanban.php'; (new Studiofy_Kanban())->render_page();
		} else {
			echo '<div class="wrap"><h1>Projects</h1><a href="?page=studiofy-projects&view=kanban" class="button button-primary">View Kanban Board</a><br><br>';
			require_once STUDIOFY_PATH.'admin/list-tables/class-studiofy-project-list-table.php';
			$t = new Studiofy_Project_List_Table(); $t->prepare_items(); $t->display(); echo '</div>';
		}
	}
}
