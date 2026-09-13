# Usage

Suppose your site has Recipes and Ingredients sections, with handles `recipes` and `ingredients`. You want editors to select ingredients on a recipe and also manage that relationship from an ingredient. Many to Many exposes the other side of the same Craft relation.

Create an Entries field called Related Ingredients (`relatedIngredients`) that selects entries from Ingredients, and add it to the Recipes entry layout. On the Ingredients layout, add a Many to Many field called Related Recipes, pointing to the Recipes section and its Related Ingredients field. The general setup is:
1. Create your initial relationship field using the Entries Field Type and attach it to your first section.
2. Create another field that attaches the relationship using the Many to Many field type.
  * *Linked Section* will be the initial section that contains the relationship. (for example, Recipes).
  * *Associated Field* is the field on the other end of this relationship. (for example, Related Ingredients).
3. Attach the newly created Many to Many field to your section.

Open a recipe, select an ingredient and save it. Open that ingredient and check the reverse field for the recipe. Change the selection there, save, and reopen the recipe to confirm the relationship changed from both sides.

## Template Usage
Since this plugin relies on Craft's built in relationships, you can continue to use relationships just as you always have.

Put this example in a recipe entry template:

```twig
<h1>Related Ingredients</h1>

{% set relatedIngredients = craft.entries.section('ingredients').relatedTo(entry).all() %}

{% for ingredient in relatedIngredients %}
    {{ ingredient.title }}<br />
{% endfor %}
```

Put this example in an ingredient entry template:

```twig
<h1>Related Recipes</h1>

{% set relatedRecipes = craft.entries.section('recipes').relatedTo(entry).all() %}

{% for recipe in relatedRecipes %}
    {{ recipe.title }}<br />
{% endfor %}
```
