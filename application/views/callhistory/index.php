<div class="container">
    <h2>Call History</h2>
    <p class="text-muted">Upload N1MM call history files (.txt/.csv), assign an organization label (e.g., FOC), and set file priority for matching.</p>

    <?php if (!empty($preview)) { ?>
    <div class="card mb-3 border-warning">
        <div class="card-header bg-warning text-dark">
            <strong><i class="fas fa-search"></i> Scan Preview &mdash; <?php echo htmlspecialchars($scan_file->original_filename); ?></strong>
            <span class="badge bg-dark ms-2"><?php echo count($preview); ?> QSO(s) with blank SIG fields found</span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-2">Only QSOs with <strong>no existing SIG data</strong> are shown. Review the proposed values below, then click <strong>Apply Selected</strong> to write them.</p>
            <form method="post" action="<?php echo site_url('callhistory/scan_apply'); ?>" id="apply-form">
                <input type="hidden" name="file_id" value="<?php echo (int)$scan_file->id; ?>">
                <div class="mb-2 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all-btn"><i class="fas fa-check-double"></i> Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselect-all-btn"><i class="fas fa-times"></i> Deselect All</button>
                    <button type="submit" class="btn btn-sm btn-success" id="apply-btn"><i class="fas fa-save"></i> Apply Selected (<span id="selected-count"><?php echo count($preview); ?></span>)</button>
                </div>
                <div class="table-responsive">
                    <table id="callhistory-preview-table" class="table table-sm table-striped table-hover w-100">
                        <thead>
                            <tr>
                                <th style="width:30px;"><input type="checkbox" id="check-all" checked></th>
                                <th>Callsign</th>
                                <th>Date/Time</th>
                                <th>Band</th>
                                <th>Mode</th>
                                <th>Station Location</th>
                                <th>SIG &rarr;</th>
                                <th>SIG Info &rarr;</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview as $i => $row) { ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="row-check" name="changes[<?php echo $i; ?>][qso_id]"
                                           value="<?php echo (int)$row['qso_id']; ?>" checked>
                                    <input type="hidden" name="changes[<?php echo $i; ?>][station_id]" value="<?php echo (int)$row['station_id']; ?>">
                                    <input type="hidden" name="changes[<?php echo $i; ?>][new_sig]" value="<?php echo htmlspecialchars($row['new_sig']); ?>">
                                    <input type="hidden" name="changes[<?php echo $i; ?>][new_sig_info]" value="<?php echo htmlspecialchars($row['new_sig_info']); ?>">
                                </td>
                                <td><?php echo htmlspecialchars($row['callsign']); ?></td>
                                <td><?php echo htmlspecialchars($row['time_on']); ?></td>
                                <td><?php echo htmlspecialchars($row['band']); ?></td>
                                <td><?php echo htmlspecialchars($row['mode']); ?></td>
                                <td><?php echo htmlspecialchars($row['station_location']); ?></td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['new_sig']); ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['new_sig_info']); ?></span></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
    <?php } elseif (isset($scan_file)) { ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> No blank-SIG matches found for <strong><?php echo htmlspecialchars($scan_file->original_filename); ?></strong> in the selected logbook scope.</div>
    <?php } ?>

    <?php if (!empty($files)) { ?>
    <div class="card mb-3">
        <div class="card-header">
            <strong><i class="fas fa-search-plus"></i> Scan Logbook</strong>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo site_url('callhistory/scan_preview'); ?>" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="scan_file_id" class="form-label">Call History File</label>
                    <select class="form-select" id="scan_file_id" name="file_id" required>
                        <?php foreach ($files as $f) { ?>
                        <option value="<?php echo (int)$f->id; ?>" <?php echo (isset($scan_file) && (int)$scan_file->id === (int)$f->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f->file_label ?: $f->original_filename); ?>
                            <?php if (!empty($f->organization_label)) { echo '(' . htmlspecialchars($f->organization_label) . ')'; } ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="scan_logbook_id" class="form-label">Station Location Scope</label>
                    <select class="form-select" id="scan_logbook_id" name="logbook_id">
                        <option value="">All my station locations</option>
                        <?php if (!empty($logbooks)) { foreach ($logbooks as $lb) { ?>
                        <option value="<?php echo (int)$lb->logbook_id; ?>" <?php echo (isset($selected_logbook_id) && (int)$selected_logbook_id === (int)$lb->logbook_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lb->logbook_name); ?>
                        </option>
                        <?php } } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-search"></i> Preview Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php } ?>

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
                    <label for="organization_label" class="form-label">Organization Label</label>
                    <input type="text" class="form-control" id="organization_label" name="organization_label" maxlength="40" placeholder="FOC" required>
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

<?php if (!empty($preview)) { ?>
<script>
$(document).ready(function () {
    var table = $('#callhistory-preview-table').DataTable({
        "pageLength": 25,
        "order": [[2, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": 0 },
            { "orderable": false, "targets": 6 },
            { "orderable": false, "targets": 7 }
        ],
        "language": {
            url: getDataTablesLanguageUrl()
        }
    });

    function updateSelectedCount() {
        var count = $('#callhistory-preview-table tbody .row-check:checked').length;
        $('#selected-count').text(count);
    }

    $('#check-all').on('change', function () {
        var checked = $(this).prop('checked');
        $('#callhistory-preview-table tbody .row-check').prop('checked', checked);
        updateSelectedCount();
    });

    $('#select-all-btn').on('click', function () {
        $('#callhistory-preview-table tbody .row-check').prop('checked', true);
        $('#check-all').prop('checked', true);
        updateSelectedCount();
    });

    $('#deselect-all-btn').on('click', function () {
        $('#callhistory-preview-table tbody .row-check').prop('checked', false);
        $('#check-all').prop('checked', false);
        updateSelectedCount();
    });

    $('#callhistory-preview-table tbody').on('change', '.row-check', function () {
        updateSelectedCount();
    });

    $('#apply-form').on('submit', function (e) {
        // Disable checkboxes that are unchecked so their hidden inputs are not submitted
        $('#callhistory-preview-table tbody .row-check:not(:checked)').each(function () {
            var idx = $(this).attr('name').replace('[qso_id]', '');
            $('[name="' + idx + '[station_id]"],' +
              '[name="' + idx + '[new_sig]"],' +
              '[name="' + idx + '[new_sig_info]"]').prop('disabled', true);
            $(this).prop('disabled', true);
        });

        var count = $('#apply-form input.row-check:not(:disabled)').length;
        if (count === 0) {
            e.preventDefault();
            alert('No rows selected.');
        }
    });
});
</script>
<?php } ?>
