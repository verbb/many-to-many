function openModal(target, selectedSection, name, nameSpace) {

    var nonSelectable = $('#' + nameSpace + '-' + name + 'nonSelectable').val();
    nonSelectable     = JSON.parse("[" + nonSelectable + "]");

    Craft.createElementSelectorModal('Entry', {
        resizable:          true,
        storageKey:         'mTm' + target,
        sources:            ['section:' + selectedSection],
        criteria:           { status: null },
        multiSelect:        true,
        disabledElementIds: nonSelectable,
        disableOnSelect:    true,
        onCancel:           function(){},
        onSelect:           function(entries){
            if (entries.length) {
                var html = '';
                for (var i = 0; i < entries.length; i++) {
                    var entry = entries[i];

                    // Add this item to the disabled list
                    nonSelectable.push(entry.id);
                    $('#' + nameSpace + '-' + name + 'nonSelectable').val(nonSelectable);

                    // See if this entry has been previously deleted, and if so, add it back.
                    var wasDeleted = $('#mTm-toDelete-' + entry.id);
                    if (wasDeleted.length) {
                        wasDeleted.remove();
                    }

                    // Add the Entry to the DOM
                    html += '<div class="element removable unselectable" id="' + nameSpace + '-' + name + '-manyToMany-' + entry.id + '">';
                    html += '    <input type="hidden" name="' + nameSpace + '[' + name + '][add][]" value="' + entry.id + '">';
                    html += '   <a class="delete icon manyToManyDelete" data-nameSpace="' + nameSpace + '" data-name="' + name + '" data-remove="' + entry.id + '" title="Remove"></a>';
                    html += '   <div class="label">';
                    html += '        <span class="title">' + entry.label + '</span>';
                    html += '    </div>';
                    html += '</div>';
                }
                $('.' + target).append(html);
            }
        }
    });
}
$( document ).ready(function() {

    // Open The Modal
    $('.addManytoMany').unbind("click").on( "click", function() {
        var target        = $(this).attr('data-target');
        var section       = $(this).attr('data-section');
        var nameSpace     = $(this).attr('data-nameSpace');
        var name          = $(this).attr('data-name');

        target = target.replace(nameSpace + '-', '');
        openModal(target, section, name, nameSpace);
    });

    // Remove the entry
    $('.elements').on('click', 'a.manyToManyDelete', function() {

        // Setup Variables
        var toDelete  = $(this).attr('data-remove');
        var parentId  = $(this).parent().attr('id');
        var nameSpace = $(this).attr('data-nameSpace');
        var name      = $(this).attr('data-name');

        // Add the ID to the "Delete" array of inputs
        var html = '<input type="hidden" name="' + nameSpace + '[' + name + '][delete][]" value="' + toDelete + '" id="mTm-toDelete-' + toDelete + '" />';
        nameSpace = $(this).attr('data-nameSpace').replace('[','-').replace(']','');
        $('#' + nameSpace + '-' + name + '-' + 'toDelete').append(html);

        // Allow the element to be re-selected by the Modal
        var curNonSelectable = $('#' + nameSpace + '-' + name + 'nonSelectable').val();
        curNonSelectable = JSON.parse("[" + curNonSelectable + "]");
        y = jQuery.grep(curNonSelectable, function(value) {
          return value != toDelete;
        });
        $('#' + nameSpace + '-' + name + 'nonSelectable').val(y);

        // Remove the Element
        $('#' + parentId).fadeOut( 'fast', function() {
            $('#' + parentId).remove();
        });
        return false;
    });

});
