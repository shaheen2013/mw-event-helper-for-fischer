"use strict";
(function ($) {

  function initDataTable(){
    $('#mwhp-inspiration-table').each(function () {
      const $table = $(this);
      const hasRealRow = $table.find('tbody tr').toArray().some(row => {
        const $row  = $(row);
        const cells = $row.find('td');
        const isNoDataRow =
          $row.text().includes('No data found.') ||
          cells.toArray().some(td => td.hasAttribute('colspan'));
        return !isNoDataRow;
      });
      if (hasRealRow && !$table.data('dt-init')) {
        new DataTable(this, { responsive: true });
        $table.data('dt-init', true);
      }
    });
  }

  function deleteRecords(){
    const $deleteBtn = $("#delete-all-inspire-data");

    if ($deleteBtn.length) {
        $deleteBtn.on("click", function () {
            // Create modal HTML dynamically
            const $modal = $(`
                <div id="mwhp-modal-overlay" style="
                    position: fixed; inset: 0; background: rgba(0,0,0,0.5);
                    display: flex; justify-content: center; align-items: center; z-index: 9999;
                ">
                    <div id="mwhp-modal" style="
                        background: #fff; padding: 20px 25px; border-radius: 8px;
                        width: 350px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
                        text-align: center;
                    ">
                        <h2 style="margin-bottom: 15px;">Delete Records</h2>
                        <p style="margin-bottom: 10px;">Select a date. All records <strong>on or before</strong> this date will be deleted.</p>
                        <input type="text" id="mwhp-delete-date" class="regular-text" placeholder="YYYY-MM-DD" style="width:100%; text-align:center; margin-bottom:15px;">
                        <div style="display:flex; justify-content:space-between;">
                            <button class="button button-secondary" id="mwhp-cancel">Cancel</button>
                            <button class="button button-primary" id="mwhp-confirm-delete" style="background:red;border-color:red;">Delete</button>
                        </div>
                    </div>
                </div>
            `);

            $("body").append($modal);
            $("#mwhp-delete-date").datepicker({ dateFormat: "yy-mm-dd" });

            // Cancel
            $("#mwhp-cancel").on("click", function () {
                $modal.remove();
            });

            // Confirm delete
            $("#mwhp-confirm-delete").on("click", function () {
                const date = $("#mwhp-delete-date").val();
                if (!date) {
                    alert("Please select a date first.");
                    return;
                }

                if (!confirm("Are you sure you want to delete records on or before " + date + "?")) {
                    return;
                }

                $(this).prop("disabled", true).text("Deleting...");

                $.ajax({
                    url: mwhpJSObj.ajax_url,
                    method: "POST",
                    dataType: "json",
                    data: {
                        action: "mwhp_delete_inspiration_records",
                        nonce: mwhpJSObj.nonce,
                        date,
                    },
                    success: function (res) {
                        if (res.success) {
                            alert(res.data.message);
                            location.reload();
                        } else {
                            alert(res.data.message || "Failed to delete records.");
                        }
                    },
                    error: function () {
                        alert("AJAX error occurred.");
                    },
                    complete: function () {
                        $modal.remove();
                    },
                });
            });
        });
    }
  }

  $(document).ready(function() {
    initDataTable()
    deleteRecords()
  });
})(jQuery);
