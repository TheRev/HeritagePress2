// Handles the Find and Link Media modal for the person edit screen
jQuery(document).ready(function ($) {
  // Open modal
  $(document).on("click", "#hp-find-link-media-btn", function () {
    $("#hp-find-link-media-modal").show();
    $("#hp-media-search-input").val("");
    $("#hp-media-search-results").empty();
  });

  // Close modal
  $(document).on("click", "#hp-close-media-modal", function () {
    $("#hp-find-link-media-modal").hide();
  });

  // Search media
  $(document).on("submit", "#hp-media-search-form", function (e) {
    e.preventDefault();
    var search = $("#hp-media-search-input").val();
    var tree = $("#hp-media-search-tree").val();
    var media_type = $("#hp-media-search-type").val();
    var nonce = hpFindLinkMedia.nonce;
    $("#hp-media-search-results").html("<p>Searching...</p>");
    $.post(
      ajaxurl,
      {
        action: "hp_get_media_list",
        search: search,
        tree: tree,
        media_type: media_type,
        nonce: nonce,
        per_page: 20,
        page: 1,
      },
      function (response) {
        if (response.success) {
          var items = response.data.items;
          if (items.length === 0) {
            $("#hp-media-search-results").html("<p>No media found.</p>");
          } else {
            var html = '<ul class="hp-media-search-list">';
            items.forEach(function (item) {
              html +=
                "<li>" +
                '<input type="radio" name="hp-media-select" value="' +
                item.mediaID +
                '" data-title="' +
                _.escape(item.description || item.path) +
                '"> ' +
                _.escape(item.description || item.path) +
                ' <span class="hp-media-type">[' +
                _.escape(item.media_type_name || item.mediatypeID) +
                "]</span>" +
                "</li>";
            });
            html += "</ul>";
            $("#hp-media-search-results").html(html);
          }
        } else {
          $("#hp-media-search-results").html(
            "<p>Error: " + response.data + "</p>"
          );
        }
      }
    );
  });

  // Link selected media
  $(document).on("click", "#hp-link-media-btn", function () {
    var selected = $('input[name="hp-media-select"]:checked').val();
    if (!selected) {
      alert("Please select a media item to link.");
      return;
    }
    var person_id = $('input[name="personID"]').val() || window.hpPersonID;
    var tree = $('input[name="tree"]').val() || window.hpTree;
    if (!person_id || !tree) {
      alert("Missing person or tree ID.");
      return;
    }
    var nonce = hpFindLinkMedia.linkNonce;
    $.post(
      ajaxurl,
      {
        action: "hp_link_media_to_person",
        media_id: selected,
        person_id: person_id,
        tree: tree,
        nonce: nonce,
      },
      function (response) {
        if (response.success) {
          $("#hp-find-link-media-modal").hide();
          // Refresh linked media list
          loadLinkedMedia(person_id, tree);
        } else {
          alert("Error: " + (response.data || "Failed to link media."));
        }
      }
    );
  });

  // Helper: Load linked media for person
  function loadLinkedMedia(person_id, tree) {
    var nonce = hpFindLinkMedia.getLinkedNonce;
    $("#hp-linked-media-list").html("<p>Loading linked media...</p>");
    $.post(
      ajaxurl,
      {
        action: "hp_get_linked_media_for_person",
        person_id: person_id,
        tree: tree,
        nonce: nonce,
      },
      function (response) {
        if (response.success) {
          var items = response.data.items;
          if (items.length === 0) {
            $("#hp-linked-media-list").html("<p>No media linked.</p>");
          } else {
            var html = '<ul class="hp-linked-media-list">';
            items.forEach(function (item) {
              html +=
                "<li>" +
                _.escape(item.description || item.path) +
                ' <span class="hp-media-type">[' +
                _.escape(item.mediatypeID) +
                "]</span>" +
                "</li>";
            });
            html += "</ul>";
            $("#hp-linked-media-list").html(html);
          }
        } else {
          $("#hp-linked-media-list").html("<p>Error loading linked media.</p>");
        }
      }
    );
  }

  // On page load, try to load linked media if person/tree available
  var person_id = $('input[name="personID"]').val() || window.hpPersonID;
  var tree = $('input[name="tree"]').val() || window.hpTree;
  if (person_id && tree) {
    loadLinkedMedia(person_id, tree);
  }
});
