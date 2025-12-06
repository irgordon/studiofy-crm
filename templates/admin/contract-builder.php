<div class="wrap">
    <h1><?php echo $contract ? 'Edit Contract' : 'New Contract'; ?></h1>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="studiofy_save_contract">
        <?php wp_nonce_field('save_contract', 'studiofy_nonce'); ?>
        <?php if ($contract) echo '<input type="hidden" name="contract_id" value="' . $contract->id . '">'; ?>

        <div class="studiofy-panel">
            <label>Contract Title *</label>
            <input type="text" name="title" required class="widefat" value="<?php echo $contract->title ?? ''; ?>">
            
            <div class="studiofy-form-row" style="margin-top:10px;">
                <div class="studiofy-col"><label>Client *</label>
                    <select name="customer_id" required class="widefat">
                        <option value="">Select client</option>
                        <?php foreach($customers as $c) echo "<option value='{$c->id}' " . selected($contract->customer_id ?? 0, $c->id, false) . ">{$c->first_name} {$c->last_name}</option>"; ?>
                    </select>
                </div>
                <div class="studiofy-col"><label>Project (Optional)</label>
                    <select name="project_id" class="widefat">
                        <option value="">Select project</option>
                        <?php foreach($projects as $p) echo "<option value='{$p->id}' " . selected($contract->project_id ?? 0, $p->id, false) . ">{$p->title}</option>"; ?>
                    </select>
                </div>
            </div>

            <div class="studiofy-form-row" style="margin-top:10px;">
                <div class="studiofy-col"><label>Start Date *</label><input type="date" name="start_date" required class="widefat" value="<?php echo $contract->start_date ?? ''; ?>"></div>
                <div class="studiofy-col"><label>End Date *</label><input type="date" name="end_date" required class="widefat" value="<?php echo $contract->end_date ?? ''; ?>"></div>
                <div class="studiofy-col"><label>Contract Value *</label><input type="number" step="0.01" name="amount" required class="widefat" value="<?php echo $contract->amount ?? '0'; ?>"></div>
            </div>
            
            <div style="margin-top:10px;">
                <label>Status</label>
                <select name="status" class="widefat">
                    <option value="Draft" <?php selected($contract->status ?? '', 'Draft'); ?>>Draft</option>
                    <option value="Active" <?php selected($contract->status ?? '', 'Active'); ?>>Active</option>
                </select>
            </div>
        </div>

        <div class="studiofy-panel" style="margin-top:20px;">
            <h3>Contract Content & Terms</h3>
            <p>Use the editor below to customize the legal text. You can add or remove clauses.</p>
            <?php 
            // Use the variable passed from controller
            wp_editor($content_to_edit, 'body_content', [
                'media_buttons' => false, 
                'textarea_rows' => 25,
                'teeny' => false
            ]); 
            ?>
        </div>

        <?php submit_button('Save Contract'); ?>
    </form>
</div>
