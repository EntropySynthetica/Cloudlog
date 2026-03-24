<div class="container">
    <h2>Call History</h2>
    <p class="text-muted">Upload N1MM call history files (.txt/.csv), assign an optional organization label (e.g., FOC), and set file priority for matching.</p>

    <div class="card mb-3">
        <div class="card-header">
            <strong>Upload Call History File</strong>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo site_url('callhistory/upload'); ?>" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-4">
                    <label for="history_file" class="form-label">File</label>
                    <input type="file" class="form-control" id="history_file" name="history_file" accept=".txt,.csv" required>
                </div>
                <div class="col-md-3">
                    <label for="file_label" class="form-label">File Label (optional)</label>
                    <input type="text" class="form-control" id="file_label" name="file_label" maxlength="100" placeholder="FOC CW List 2026">
                </div>
                <div class="col-md-3">
                    <label for="organization_label" class="form-label">Organization Label (optional)</label>
                    <input type="text" class="form-control" id="organization_label" name="organization_label" maxlength="40" placeholder="FOC">
                </div>
                <div class="col-md-2">
                    <label for="priority" class="form-label">Priority</label>
                    <input type="number" class="form-control" id="priority" name="priority" value="0">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Your Call History Files</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>File</th>
                        <th>Label</th>
                        <th>Org</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($files)) { ?>
                        <?php foreach ($files as $file) { ?>
                            <tr>
                                <td><?php echo (int)$file->id; ?></td>
                                <td><?php echo htmlspecialchars($file->original_filename); ?></td>
                                <td><?php echo htmlspecialchars($file->file_label); ?></td>
                                <td><?php echo htmlspecialchars($file->organization_label); ?></td>
                                <td>
                                    <form method="post" action="<?php echo site_url('callhistory/set_priority'); ?>" class="d-flex gap-2">
                                        <input type="hidden" name="id" value="<?php echo (int)$file->id; ?>">
                                        <input type="number" name="priority" class="form-control form-control-sm" style="max-width: 90px;" value="<?php echo (int)$file->priority; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <?php if ((int)$file->is_active === 1) { ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php } else { ?>
                                        <span class="badge text-bg-secondary">Disabled</span>
                                    <?php } ?>
                                </td>
                                <td><?php echo htmlspecialchars($file->uploaded_at); ?></td>
                                <td>
                                    <form method="post" action="<?php echo site_url('callhistory/set_active'); ?>" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo (int)$file->id; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo (int)$file->is_active === 1 ? '0' : '1'; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <?php echo (int)$file->is_active === 1 ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                    <form method="post" action="<?php echo site_url('callhistory/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this call history file?');">
                                        <input type="hidden" name="id" value="<?php echo (int)$file->id; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8" class="text-center p-3 text-muted">No call history files uploaded yet.</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
