// ==========================================================================

// Many to Many Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

if (typeof Craft.ManyToMany === typeof undefined) {
    Craft.ManyToMany = {};
}

(function($) {

Craft.ManyToMany.Field = Garnish.Base.extend({
    elementsToDelete: [],

    init: function(settings) {
        this.id = settings.id;
        this.name = settings.name;
        this.elementIds = settings.elementIds;
        this.$container = $('#' + settings.id + '-field .js-mtm-field');
        this.$deleteContainer = this.$container.find('.js-mtm-delete');
        this.$elementSelect = this.$container.find('.js-mtm-element-select');
        this.elementSelect = this.$elementSelect.data('elementSelect');

        // Use Garnish events so Craft's own onSelect/onRemove handlers still run
        this.elementSelect.on('selectElements', $.proxy(this, 'onSelectElements'));
        this.elementSelect.on('removeElements', $.proxy(this, 'onRemoveElements'));
    },

    onSelectElements: function(ev) {
        const self = this;
        const elements = ev.elements || [];

        $.each(elements, function(index, value) {
            var id = typeof value === 'object' && value !== null ? (value.id || $(value).data('id')) : value;
            var i = self.elementsToDelete.indexOf(id);

            if (i === -1 && typeof id === 'string') {
                i = self.elementsToDelete.indexOf(parseInt(id, 10));
            }

            if (i !== -1) {
                self.elementsToDelete.splice(i, 1);
            }
        });

        this.updateDeletedElements();
    },

    onRemoveElements: function() {
        const self = this;

        // $elements is a jQuery collection of chip roots (with data-id), not a parent
        // container — use filter(), not find(), matching Craft's ElementSelectInput.
        $.each(this.elementIds, function(index, value) {
            if (!self.elementSelect.$elements.filter('[data-id="' + value + '"]').length) {
                if (self.elementsToDelete.indexOf(value) === -1) {
                    self.elementsToDelete.push(value);
                }
            }
        });

        this.updateDeletedElements();
    },

    updateDeletedElements: function() {
        const self = this;

        this.$deleteContainer.html('');

        $.each(this.elementsToDelete, function(index, value) {
            let html = '<input type="hidden" name="' + self.name + '[delete][]" value="' + value + '" />';

            self.$deleteContainer.append(html);
        });
    },
});

})(jQuery);
