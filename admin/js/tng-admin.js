/**
 * TNG Import/Export Admin JavaScript
 */

jQuery(document).ready(function ($) {
  // Test TNG database connection
  $("#test-tng-connection").on("click", function () {
    var $button = $(this);
    var $form = $("#tng-connection-form");
    var $status = $("#connection-status");

    $button.prop("disabled", true).text(hpTngAjax.strings.testing_connection);
    $status.hide();

    var formData = {
      action: "hp_test_tng_connection",
      nonce: hpTngAjax.nonce,
      host: $form.find('[name="host"]').val(),
      database: $form.find('[name="database"]').val(),
      username: $form.find('[name="username"]').val(),
      password: $form.find('[name="password"]').val(),
      table_prefix: $form.find('[name="table_prefix"]').val(),
    };

    $.post(hpTngAjax.ajax_url, formData)
      .done(function (response) {
        if (response.success) {
          $status.html(
            '<div class="notice notice-success"><p>' +
              response.data.message +
              "</p></div>"
          );
          $("#import-options").show();

          if (!response.data.validation.valid) {
            var issues =
              '<div class="notice notice-warning"><p><strong>Database Issues Found:</strong></p><ul>';
            if (response.data.validation.missing_tables.length > 0) {
              issues +=
                "<li>Missing tables: " +
                response.data.validation.missing_tables.join(", ") +
                "</li>";
            }
            if (response.data.validation.issues.length > 0) {
              response.data.validation.issues.forEach(function (issue) {
                issues += "<li>" + issue + "</li>";
              });
            }
            issues += "</ul></div>";
            $status.append(issues);
          }
        } else {
          $status.html(
            '<div class="notice notice-error"><p>' +
              response.data.message +
              "</p></div>"
          );
        }
        $status.show();
      })
      .fail(function () {
        $status
          .html(
            '<div class="notice notice-error"><p>Connection test failed.</p></div>'
          )
          .show();
      })
      .always(function () {
        $button.prop("disabled", false).text("Test Connection");
      });
  });

  // Handle TNG import
  $("#tng-import-form").on("submit", function (e) {
    e.preventDefault();

    var $form = $(this);
    var $progress = $("#import-progress");
    var $status = $("#import-status");

    $progress.show();
    $status.text(hpTngAjax.strings.importing_data);

    // Get connection data
    var $connectionForm = $("#tng-connection-form");
    var formData = {
      action: "hp_import_tng_data",
      nonce: hpTngAjax.nonce,
      host: $connectionForm.find('[name="host"]').val(),
      database: $connectionForm.find('[name="database"]').val(),
      username: $connectionForm.find('[name="username"]').val(),
      password: $connectionForm.find('[name="password"]').val(),
      table_prefix: $connectionForm.find('[name="table_prefix"]').val(),
      gedcom_filter: $connectionForm.find('[name="gedcom_filter"]').val(),
      import_sources: $form.find('[name="import_sources"]').is(":checked"),
      import_media: $form.find('[name="import_media"]').is(":checked"),
      create_backup: $form.find('[name="create_backup"]').is(":checked"),
    };

    $.post(hpTngAjax.ajax_url, formData)
      .done(function (response) {
        if (response.success) {
          var results = response.data.results;
          var statusHtml =
            '<div class="notice notice-success"><p><strong>Import Completed!</strong></p>';
          statusHtml += "<ul>";
          statusHtml += "<li>People: " + results.people + "</li>";
          statusHtml += "<li>Families: " + results.families + "</li>";
          statusHtml += "<li>Children: " + results.children + "</li>";
          statusHtml += "<li>Events: " + results.events + "</li>";
          statusHtml += "<li>Sources: " + results.sources + "</li>";
          statusHtml += "<li>Media: " + results.media + "</li>";
          statusHtml += "<li>Citations: " + results.citations + "</li>";
          if (results.errors > 0) {
            statusHtml +=
              '<li style="color: red;">Errors: ' + results.errors + "</li>";
          }
          statusHtml += "</ul></div>";

          if (response.data.log && response.data.log.length > 0) {
            statusHtml += "<details><summary>Import Log</summary><ul>";
            response.data.log.forEach(function (entry) {
              var className = entry.type === "error" ? "error" : "info";
              statusHtml +=
                '<li class="' +
                className +
                '">[' +
                entry.timestamp +
                "] " +
                entry.message +
                "</li>";
            });
            statusHtml += "</ul></details>";
          }

          $status.html(statusHtml);
        } else {
          $status.html(
            '<div class="notice notice-error"><p>' +
              response.data.message +
              "</p></div>"
          );
        }
      })
      .fail(function () {
        $status.html(
          '<div class="notice notice-error"><p>Import failed.</p></div>'
        );
      });
  });

  // Handle TNG export
  $("#tng-export-form").on("submit", function (e) {
    e.preventDefault();

    var $form = $(this);
    var $progress = $("#export-progress");
    var $status = $("#export-status");

    $progress.show();
    $status.text(hpTngAjax.strings.exporting_data);

    var formData = {
      action: "hp_export_tng_data",
      nonce: hpTngAjax.nonce,
      gedcom_filter: $form.find('[name="gedcom_filter"]').val(),
      export_format: $form.find('[name="export_format"]:checked').val(),
      export_people: $form.find('[name="export_people"]').is(":checked"),
      export_families: $form.find('[name="export_families"]').is(":checked"),
      export_events: $form.find('[name="export_events"]').is(":checked"),
      export_sources: $form.find('[name="export_sources"]').is(":checked"),
      export_media: $form.find('[name="export_media"]').is(":checked"),
    };

    $.post(hpTngAjax.ajax_url, formData)
      .done(function (response) {
        if (response.success) {
          var statusHtml =
            '<div class="notice notice-success"><p><strong>Export Completed!</strong></p>';
          statusHtml +=
            '<p><a href="' +
            response.data.download_url +
            '" class="button button-primary">Download Export File</a></p>';
          statusHtml += "</div>";
          $status.html(statusHtml);
        } else {
          $status.html(
            '<div class="notice notice-error"><p>' +
              response.data.message +
              "</p></div>"
          );
        }
      })
      .fail(function () {
        $status.html(
          '<div class="notice notice-error"><p>Export failed.</p></div>'
        );
      });
  });

  // Handle schema migration
  $("#schema-migration-form").on("submit", function (e) {
    e.preventDefault();

    if (
      !confirm(
        "Are you sure you want to migrate to TNG-compatible schema? This will modify your database structure."
      )
    ) {
      return;
    }

    var $form = $(this);
    var $progress = $("#migration-progress");
    var $status = $("#migration-status");

    $progress.show();
    $status.text(hpTngAjax.strings.migrating_schema);

    var formData = {
      action: "hp_migrate_to_tng_schema",
      nonce: hpTngAjax.nonce,
      preserve_data: $form.find('[name="preserve_data"]').is(":checked"),
      create_backup: $form.find('[name="create_backup"]').is(":checked"),
      map_existing_data: $form
        .find('[name="map_existing_data"]')
        .is(":checked"),
    };

    $.post(hpTngAjax.ajax_url, formData)
      .done(function (response) {
        if (response.success) {
          $status.html(
            '<div class="notice notice-success"><p>' +
              response.data.message +
              "</p><p>Please refresh the page to see the updated schema status.</p></div>"
          );
        } else {
          $status.html(
            '<div class="notice notice-error"><p>' +
              response.data.message +
              "</p></div>"
          );
        }
      })
      .fail(function () {
        $status.html(
          '<div class="notice notice-error"><p>Schema migration failed.</p></div>'
        );
      });
  });

  // Progress bar animation (simple)
  function animateProgressBar(percentage) {
    $(".progress-fill").animate({ width: percentage + "%" }, 500);
  }
});
