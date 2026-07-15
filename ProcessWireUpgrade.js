/**
 * ProcessWireUpgrade — visual checkbox clone and sync
 *
 * ProcessWire clones a submit button with `showInHeader()`:
 *   original id="ProcessWireUpgradeRefresh"
 *   copy    id="ProcessWireUpgradeRefresh_copy"
 * The copy triggers the original on click, so the real form submits
 * normally. We only need to clone a visual checkbox beside it.
 *
 */
$(function() {
  const $head = $('#pw-content-head-buttons');
  const $source = $('#ProcessWireUpgradeGithubTracking').closest('label');
  if(!$head.length || !$source.length) return;

  function addHeaderCheckbox() {
    const $button = $('#ProcessWireUpgradeRefresh_copy');
    if(!$button.length) return false;
    if($('.ProcessWireUpgradeGithubTrackingHeader').length) return true;

    const $clone = $source.clone(true);
    $clone.addClass('ProcessWireUpgradeGithubTrackingHeader');
    $clone.find('input')
      .removeAttr('name')
      .attr('id', 'ProcessWireUpgradeGithubTracking_copy');

    const $controls = $('<div class="ProcessWireUpgradeHeaderControls"></div>');
    $button.before($controls);
    $controls.append($button, $clone);

    $clone.find('input').on('change', function() {
      $('#ProcessWireUpgradeGithubTracking')
        .prop('checked', this.checked)
        .trigger('change');
    });

    $('#ProcessWireUpgradeGithubTracking').on('change', function() {
      $('#ProcessWireUpgradeGithubTracking_copy')
        .prop('checked', this.checked);
    });

    return true;
  }

  if(addHeaderCheckbox()) return;

  const observer = new MutationObserver(function() {
    if(addHeaderCheckbox()) observer.disconnect();
  });

  observer.observe($head[0], { childList: true });
});
