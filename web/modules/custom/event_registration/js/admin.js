/**
 * @file
 * JavaScript for Event Registration admin page.
 *
 * Handles AJAX filtering of events and registrations in the admin listing.
 * Uses Drupal's once() library for proper behavior attachment.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.eventRegistrationAdmin = {
    attach: function (context, settings) {
      // Handle event date filter change.
      once('event-date-filter', '#edit-event-date-filter', context).forEach(function (element) {
        $(element).on('change', function () {
          var date = $(this).val();

          if (date) {
            // Fetch events for the selected date.
            $.ajax({
              url: Drupal.url('admin/event-registration/ajax/events'),
              type: 'GET',
              data: { date: date },
              dataType: 'json',
              success: function (response) {
                var $eventNameSelect = $('#edit-event-name-filter');
                $eventNameSelect.empty();
                $eventNameSelect.append('<option value="">' + Drupal.t('- Select Event -') + '</option>');
                
                if (response && typeof response === 'object') {
                  $.each(response, function (id, name) {
                    $eventNameSelect.append('<option value="' + id + '">' + name + '</option>');
                  });
                }
              }
            });
          } else {
            // Reset event name dropdown.
            var $eventNameSelect = $('#edit-event-name-filter');
            $eventNameSelect.empty();
            $eventNameSelect.append('<option value="">' + Drupal.t('- Select Event -') + '</option>');
          }

          // Reset the table.
          $('#registrations-table tbody').empty();
          $('#count-value').text('0');
        });
      });

      // Handle filter button click.
      once('filter-btn', '#filter-registrations-btn', context).forEach(function (element) {
        $(element).on('click', function (e) {
          e.preventDefault();
          var eventId = $('#edit-event-name-filter').val();

          if (eventId) {
            // Fetch registrations for the selected event.
            $.ajax({
              url: Drupal.url('admin/event-registration/ajax/registrations'),
              type: 'GET',
              data: { event_id: eventId },
              dataType: 'json',
              success: function (response) {
                updateTable(response);
                updateExportLink(eventId);
              }
            });
          } else {
            alert(Drupal.t('Please select both an event date and event name.'));
          }
        });
      });

      /**
       * Updates the registrations table.
       */
      function updateTable(data) {
        var $tbody = $('#registrations-table tbody');
        $tbody.empty();

        $('#count-value').text(data.count);

        if (data.rows && data.rows.length > 0) {
          $.each(data.rows, function (index, row) {
            var $tr = $('<tr>');
            $tr.append($('<td>').text(row.name));
            $tr.append($('<td>').text(row.email));
            $tr.append($('<td>').text(row.event_date));
            $tr.append($('<td>').text(row.college_name));
            $tr.append($('<td>').text(row.department));
            $tr.append($('<td>').text(row.submission_date));
            $tbody.append($tr);
          });
        } else {
          var $tr = $('<tr>');
          $tr.append($('<td colspan="6">').text(Drupal.t('No registrations found for this event.')));
          $tbody.append($tr);
        }
      }

      /**
       * Updates the CSV export link with event_id parameter.
       */
      function updateExportLink(eventId) {
        var $exportLink = $('#export-csv-link');
        var baseUrl = Drupal.url('admin/event-registration/export-csv');
        if (eventId) {
          $exportLink.attr('href', baseUrl + '?event_id=' + eventId);
        } else {
          $exportLink.attr('href', baseUrl);
        }
      }
    }
  };

})(jQuery, Drupal, once);
