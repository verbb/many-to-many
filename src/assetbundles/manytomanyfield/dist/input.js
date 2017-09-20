function openModal(target, selectedSection, name, nameSpace) {

    let nonSelectable = $('#' + nameSpace + '-' + name + 'nonSelectable').val();
    nonSelectable = JSON.parse("[" + nonSelectable + "]");

    Craft.createElementSelectorModal('craft\\elements\\Entry', {
        resizable: true,
        storageKey: 'mTm' + target,
        sources: ['section:' + selectedSection],
        criteria: {status: null, localeEnabled: null},
        multiSelect: true,
        disabledElementIds: nonSelectable,
        disableOnSelect: true,
        onCancel: function () {
        },
        onSelect: function (entries) {
            if (entries.length) {
                let html = '';
                for (let i = 0; i < entries.length; i++) {
                    let entry = entries[i];

                    // Add this item to the disabled list
                    nonSelectable.push(entry.id);
                    $('#' + nameSpace + '-' + name + 'nonSelectable').val(nonSelectable);

                    // See if this entry has been previously deleted, and if so, add it back.
                    let wasDeleted = $('#mTm-toDelete-' + entry.id);
                    if (wasDeleted.length) {
                        wasDeleted.remove();
                    }

                    // Add the Entry to the DOM
                    html += '<div class="element removable unselectable" id="' + nameSpace + '-' + name + '-manyToMany-' + entry.id + '">' +
                        '    <input type="hidden" name="' + nameSpace + '[' + name + '][add][]" value="' + entry.id + '">' +
                        '   <a class="delete icon manyToManyDelete" data-nameSpace="' + nameSpace + '" data-name="' + name + '" data-remove="' + entry.id + '" title="Remove"></a>' +
                        '   <div class="label">' +
                        '        <span class="title">' + entry.label + '</span>' +
                        '    </div>' +
                        '</div>';
                }

                $('.' + target).append(html);
            }
        }
    });
}

$(document).ready(function () {

    // Open The Modal
    $('.addManytoMany').unbind('click').on('click', function () {
        let target = $(this).attr('data-target');
        let section = $(this).attr('data-section');
        let nameSpace = $(this).attr('data-nameSpace');
        let name = $(this).attr('data-name');

        target = target.replace(nameSpace + '-', '');
        openModal(target, section, name, nameSpace);
    });

    // Remove the entry
    $('.elements').on('click', 'a.manyToManyDelete', function () {

        // Setup Variables
        let toDelete = $(this).attr('data-remove');
        let parentId = $(this).parent().attr('id');
        let nameSpace = $(this).attr('data-nameSpace');
        let name = $(this).attr('data-name');

        // Add the ID to the "Delete" array of inputs
        let html = '<input type="hidden" name="' + nameSpace + '[' + name + '][delete][]" value="' + toDelete + '" id="mTm-toDelete-' + toDelete + '" />';
        nameSpace = $(this).attr('data-nameSpace').replace('[', '-').replace(']', '');
        $('#' + nameSpace + '-' + name + '-' + 'toDelete').append(html);

        // Allow the element to be re-selected by the Modal
        let curNonSelectable = $('#' + nameSpace + '-' + name + 'nonSelectable').val();
        curNonSelectable = JSON.parse("[" + curNonSelectable + "]");
        y = jQuery.grep(curNonSelectable, function (value) {
            return value != toDelete;
        });

        // Remove the Element
        $('#' + parentId).fadeOut('fast', function () {
            $('#' + parentId).remove();
        });
        return false;
    });

});
