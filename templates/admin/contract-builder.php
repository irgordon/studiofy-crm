<div class="wrap studiofy-dark-theme">
    <h1><?php echo $contract ? 'Edit Contract' : 'New Contract'; ?></h1>
    <form method="post" action="<?php echo admin_url('admin_post.php'); ?>">
        <input type="hidden" name="action" value="studiofy_save_contract">
        <?php wp_nonce_field('save_contract', 'studiofy_nonce'); ?>
        <?php if ($contract) echo '<input type="hidden" name="contract_id" value="' . $contract->id . '">'; ?>

        <div class="studiofy-panel">
            <label>Contract Title *</label>
            <input type="text" name="title" required class="widefat" value="<?php echo $contract->title ?? ''; ?>">
            
            <div class="studiofy-form-row" style="margin-top:10px;">
                <div class="studiofy-col"><label>Client *</label>
                    <select name="client_id" required class="widefat">
                        <option value="">Select client</option>
                        <?php foreach($clients as $c) echo "<option value='{$c->id}'>{$c->first_name} {$c->last_name}</option>"; ?>
                    </select>
                </div>
                <div class="studiofy-col"><label>Project (Optional)</label>
                    <select name="project_id" class="widefat">
                        <option value="">Select project</option>
                        <?php foreach($projects as $p) echo "<option value='{$p->id}'>{$p->title}</option>"; ?>
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
                    <option value="Draft">Draft</option><option value="Active">Active</option>
                </select>
            </div>
        </div>

        <div class="studiofy-panel" style="margin-top:20px;">
            <h3>Contract Terms</h3>
            <?php wp_editor($contract->body_content ?? '', 'body_content', ['media_buttons' => false, 'textarea_rows' => 15]); ?>
        </div>

        <?php submit_button('Create Contract'); ?>
    </form>
</div>
