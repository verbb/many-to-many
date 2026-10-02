# Usage

Suppose your site has Recipes and Ingredients sections, with handles `recipes` and `ingredients`. You want editors to select ingredients on a recipe and also manage that relationship from an ingredient. Many to Many exposes the other side of the same Craft relation.

Many to Many supports Craft entries, categories, users and assets. The linked element owns the native relation field, while the element containing the Many to Many field is the target of that native field. Each Many to Many field uses one linked section, category group, user group or asset volume. User sources can also include all users.

## Set Up the Relationship

Create an Entries field called Related Ingredients (`relatedIngredients`) that selects entries from Ingredients, and add it to the Recipes entry layout. This field owns the underlying Craft relation.

Next, create a Many to Many field called Related Recipes (`relatedRecipes`) and configure it with these settings:

- **Linked Element Type** — choose Entries.
- **Linked Section** — choose Recipes, the section containing the entries that own the Related Ingredients field. Category, user and asset relationships show the corresponding group or volume setting instead.
- **Linked Entry Types** — optionally limit selection to particular entry types within the linked section.
- **Associated Field** — choose Related Ingredients, the Entries field that stores the relation. Its target element type must match the element layout where you add the Many to Many field.
- **Max Relations** — optionally limit how many linked elements an author can select from this side of the relationship.
- **View Mode** — choose whether authors see the selected elements as a list, inline list, cards or a card grid.

Add Related Recipes to the Ingredients entry layout. The completed relationship lets editors choose ingredients from a recipe or recipes from an ingredient while keeping the same underlying relation in sync.

![The Related Ingredients field on a recipe](../../screenshots/many-to-many-destination.png)

![The reverse Related Recipes field on an ingredient](../../screenshots/many-to-many-source.png)

Open a recipe, select an ingredient and save it. Open that ingredient and check the reverse field for the recipe. Change the selection there, save, and reopen the recipe to confirm the relationship changed from both sides.

## Access Related Elements in Twig

The Many to Many field returns its related elements directly. Put this example in an ingredient entry template, where `entry` is the current ingredient:

```twig
<h1>Related Recipes</h1>

<ul>
    {% for recipe in entry.relatedRecipes %}
        <li><a href="{{ recipe.url }}">{{ recipe.title }}</a></li>
    {% else %}
        <li>No recipes use this ingredient.</li>
    {% endfor %}
</ul>
```

When listing several ingredients, eager-load the field so Craft can fetch their related recipes together rather than running another query for each ingredient:

```twig
{% set ingredients = craft.entries()
    .section('ingredients')
    .with(['relatedRecipes'])
    .all() %}

{% for ingredient in ingredients %}
    <h2>{{ ingredient.title }}</h2>

    <ul>
        {% for recipe in ingredient.relatedRecipes %}
            <li>{{ recipe.title }}</li>
        {% else %}
            <li>No related recipes.</li>
        {% endfor %}
    </ul>
{% endfor %}
```

Because the relationship uses Craft's native relations, `relatedTo()` queries remain available. For example, this query finds recipes related to the current ingredient without referring to the reverse field handle:

```twig
{% set recipes = craft.entries()
    .section('recipes')
    .relatedTo(entry)
    .all() %}
```

## Filter by Whether a Relation Exists

The field supports Craft's `:empty:` and `:notempty:` conditions. This example finds ingredients used by at least one live recipe:

```twig
{% set usedIngredients = craft.entries()
    .section('ingredients')
    .relatedRecipes(':notempty:')
    .all() %}
```

Use `relatedRecipes(':empty:')` to find ingredients without a live related recipe. Other values aren't supported as field-query conditions; use Craft's `relatedTo()` parameter when you need to filter by a particular entry.

## Query the Field with GraphQL

Add the Ingredients section, its entry type and the Related Recipes field to the GraphQL schema used by your request. You can then select the reverse field like another entry relation:

```graphql
query Ingredients {
  entries(section: "ingredients") {
    ... on ingredients_default_Entry {
      title
      relatedRecipes {
        title
        url
      }
    }
  }
}
```

Craft generates GraphQL type names from your section and entry-type handles. Replace `ingredients_default_Entry` if your Ingredients entry type has a different generated name. The relation field accepts the standard arguments for its linked element type, so category, user and asset relationships expose their corresponding Craft GraphQL arguments.

## Understand Saving, Drafts and Revisions

Changing a Many to Many field adds or removes the current element from the associated relation field on each affected linked element. The editor must be allowed to view and save every linked element that would be modified. If any selection is outside the configured source, lacks the associated field or can't be saved by the editor, the save is rejected rather than partially updating the relationship.

Changes made in a draft stay with that draft and don't affect canonical elements until the draft is applied. Each user and draft keeps its own pending selection. Reverting an element to an earlier revision doesn't restore these reverse relationships, because Craft stores the actual relation on the linked elements rather than on the element being reverted.

## Field Constraints

Many to Many supports Craft entries, categories, users and assets, with one linked source at a time. Its Associated Field must be a non-translatable Craft relation field whose target is an entry, category, user or asset matching the element that contains the Many to Many field. The Associated Field must be present on every linked element layout editors can select; configuration and save validation prevent unsupported combinations from changing content. Commerce products and third-party source element types aren't currently included.
