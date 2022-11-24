# Many to Many plugin for Craft CMS
Many to Many is a [Craft CMS](http://www.craftcms.com) plugin which allows you to manage relationships in Craft from either of the entries that belong to the association. For example, if you have a recipe with many ingredients, and ingredients that belong to many recipes, you can manage the relationship from either the Recipe's entry or the Ingredient's entry.

## Installation
You can install Many to Many via the plugin store, or through Composer.

### Craft Plugin Store
To install **Many to Many**, navigate to the _Plugin Store_ section of your Craft control panel, search for `Many to Many`, and click the _Try_ button.

### Composer
You can also add the package to your project using Composer and the command line.

1. Open your terminal and go to your Craft project:
```shell
cd /path/to/project
```

2. Then tell Composer to require the plugin, and Craft to install it:
```shell
composer require verbb/many-to-many && php craft plugin/install many-to-many
```

## Usage
1. Create your initial relationship field using the Entries Field Type and attach it to your first section
  * This is done directly through Craft's native "Entries" field type
  * This example assumes this is done on the "Recipes" section creating a field called "Related Ingredients" that allows entries from the Ingredients section
![Recipes Screen](https://raw.githubusercontent.com/verbb/many-to-many/craft-3/screenshots/recipes-view.png)
2. Create another field that attaches the relationship using the Many to Many field type
  * This is done by creating a new field and selecting the Many to Many field type
3. Customize your Settings
  * *Linked Section* will be the initial section that contains the relationship. (in our example Recipes)
  * *Associated Field* is the field on the other end of this relationship. (in our example Related Ingredients)
![Settings Screen](https://raw.githubusercontent.com/verbb/many-to-many/craft-3/screenshots/settings.png)
4. Attach the newly created Many to Many field to your section
![Ingredients Screen](https://raw.githubusercontent.com/verbb/many-to-many/craft-3/screenshots/ingredients-view.png)

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

## Credits
Originally created by [Oberon](https://www.oberon.nl) and [page.works](https://www.page.works).

## Show your Support
Many to Many is licensed under the MIT license, meaning it will always be free and open source – we love free stuff! If you'd like to show your support to the plugin regardless, [Sponsor](https://github.com/sponsors/verbb) development.

<h2></h2>

<a href="https://verbb.io" target="_blank">
    <img width="100" src="https://verbb.io/assets/img/verbb-pill.svg">
</a>
