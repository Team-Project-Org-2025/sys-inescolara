(function () {
  'use strict';

  const COUNTER_CLASS = 'maxlength-counter';

  function createCounter($field) {
    const max = parseInt($field.attr('maxlength'), 10);
    if (!max || max <= 0) return;

    const $wrapper = $field.closest('.mb-3, .mb-2, .col-md-3, .col-md-4, .col-md-6, .col-12');
    if ($wrapper.length === 0) return;

    let $counter = $wrapper.find('.' + COUNTER_CLASS + '[data-for="' + $field.attr('name') + '"]');
    if ($counter.length > 0) return;

    $counter = $('<small>', {
      class: COUNTER_CLASS + ' text-muted d-block text-end mt-1',
      'data-for': $field.attr('name') || '',
      css: { fontSize: '0.75rem' },
    });

    if ($field.is(':visible')) {
      $field.closest('.mb-3, .mb-2').find('> .form-label, > label').first().after($counter);
    } else {
      $wrapper.append($counter);
    }

    function updateCounter() {
      const len = $field.val().length;
      const remaining = max - len;
      $counter.text(len + '/' + max);
      $counter.removeClass('text-danger text-warning');
      if (remaining <= 0) {
        $counter.addClass('text-danger fw-bold');
      } else if (remaining <= 10) {
        $counter.addClass('text-warning fw-semibold');
      }
    }

    $field.on('input change', updateCounter);
    updateCounter();
  }

  $(document).on('focusin', 'input[maxlength], textarea[maxlength]', function () {
    const $field = $(this);
    if ($field.closest('.' + COUNTER_CLASS).length === 0) {
      createCounter($field);
    }
  });

  $(document).ready(function () {
    $('input[maxlength]:visible, textarea[maxlength]:visible').each(function () {
      createCounter($(this));
    });
  });

  $(document).on('shown.bs.modal', function () {
    $('input[maxlength]:visible, textarea[maxlength]:visible').each(function () {
      createCounter($(this));
    });
  });
})();
