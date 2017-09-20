⚠️ This plugin is unfinished. ⚠️

# Many to Many Field Type plugin for Craft 3

Many to Many is a [Craft CMS](http://www.craftcms.com) plugin developed by [Page 8](http://page-8.com) and updated to support Craft 3 by [Oberon](https://www.oberon.nl). This plugin allows you to manage relationships in Craft from either of the entries that belong to the association. For example, if you have a recipe with many ingredients, and ingredients that belong to many recipes, you can manage the relationship from either the Recipe's entry or the Ingredient's entry.

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require oberonamsterdam/craft-manytomany

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Many to Many Field Type.

## Usage
1. Create your initial relationship field using the Entries Field Type and attach it to your first section
  * This is done directly through Craft's native "Entries" field type
  * This example assumes this is done on the "Recipes" section creating a field called "Related Ingredients" that allows entries from the Ingredients section
![Recipes Screen](https://raw.githubusercontent.com/page-8/craft-manytomany/master/resources/screenshots/recipes-view.png)
2. Create another field that attaches the relationship using the Many to Many field type
  * This is done by creating a new field and selecting the Many to Many field type
3. Customize your Settings
  * *Linked Section* will be the initial section that contains the relationship. (in our example Recipes)
  * *Associated Field* is the field on the other end of this relationship. (in our example Related Ingredients)
![Settings Screen](https://raw.githubusercontent.com/page-8/craft-manytomany/master/resources/screenshots/settings.png)
4. Attach the newly created Many to Many field to your section
![Ingredients Screen](https://raw.githubusercontent.com/page-8/craft-manytomany/master/resources/screenshots/ingredients-view.png)

## Template Usage
Since this plugin relies on Craft's built in relationships, you can continue to use relationships just as you always have.

**Recipes showing Related Ingredients**
```
<h1>Related Ingredients</h1>
{% set relatedIngredients = craft.entries.section('ingredients').relatedTo(entry).all() %}
{% for ingredient in relatedIngredients %}
    {{ ingredient.title }}<br />
{% endfor %}
```

**Ingredients showing Related Recipes**
```
<h1>Related Recipes</h1>
{% set relatedRecipes = craft.entries.section('recipes').relatedTo(entry).all() %}
{% for recipe in relatedRecipes %}
    {{ recipe.title }}<br />
{% endfor %}
```


## To Do
1. Allow the field to work across all (or at least more) Element Types. Currently only supports Entries
2. Allow custom ordering of the secondary relationship. Currently you can only order the primary relationship (by Craft's native ordering of the Entries field type)
3. Fix a bug that doesn't allow it to work from the modal tab
4. Other stuff I assume.

## Feedback?
Contact us on Twitter [@Page8Online](https://twitter.com/Page8Online) or visit us at [page-8.com](http://page-8.com)

## Version History
* 1.0.0 - Upgraded to support Craft 3
* 0.1.2 - Added translatable text
* 0.1.1 - Optimized the cache control. Instead of clearing all Entry types from the cache, just clears records related to the changed element
* 0.1.0 - Initial Release

### Notes
* Currently doesn't support locales
* As always, use at your own risk

### License
This work is licenced under the MIT license.
