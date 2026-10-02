// ==========================================================================

// Many to Many Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

if (typeof Craft.ManyToMany === typeof undefined) {
    Craft.ManyToMany = {};
}

(function($) {

Craft.ManyToMany.Field = Garnish.Base.extend({
    init: function(settings) {
        this.id = settings.id;
        this.name = settings.name;
        this.elementIds = settings.elementIds;
        this.$container = $('#' + settings.id + '-field .js-mtm-field');
        this.$elementSelect = this.$container.find('.js-mtm-element-select');
        this.elementSelect = this.$elementSelect.data('elementSelect');
    },
});

})(jQuery);
