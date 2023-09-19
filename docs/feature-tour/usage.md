# Usage
1. Create your initial relationship field using the Entries Field Type and attach it to your first section.
2. Create another field that attaches the relationship using the Many to Many field type.
  * *Linked Section* will be the initial section that contains the relationship. (for example, Recipes).
  * *Associated Field* is the field on the other end of this relationship. (for example, Related Ingredients).
3. Attach the newly created Many to Many field to your section.

## Template Usage
Since this plugin relies on Craft's built in relationships, you can continue to use relationships just as you always have.

```twig
<h1>Related Ingredients</h1>

{% set relatedIngredients = craft.entries.section('ingredients').relatedTo(entry).all() %}

{% for ingredient in relatedIngredients %}
    {{ ingredient.title }}<br />
{% endfor %}
```

```twig
<h1>Related Recipes</h1>

{% set relatedRecipes = craft.entries.section('recipes').relatedTo(entry).all() %}

{% for recipe in relatedRecipes %}
    {{ recipe.title }}<br />
{% endfor %}
```
