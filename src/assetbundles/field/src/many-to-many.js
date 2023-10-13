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

        this.elementSelect.onSelectElements = $.proxy(this, 'onSelectElements');
        this.elementSelect.onRemoveElements = $.proxy(this, 'onRemoveElements');
    },

    onSelectElements: function(elements) {
        const self = this;

        $.each(elements, function(index, value) {
            var i = self.elementsToDelete.indexOf(value.id);

            if (i !== -1) {
                self.elementsToDelete.splice(i, 1);
            }
        });
    },

    onRemoveElements: function() {
        const self = this;

        $.each(this.elementIds, function(index, value) {
            if (!self.elementSelect.$elements.find('[data-id="' + value + '"]').length) {
                self.elementsToDelete.push(value);
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
