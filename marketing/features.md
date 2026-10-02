<!-- feature-intro -->
Many to Many keeps an entry relationship editable from either side. Connect related entries once, then let authors manage the association from whichever entry they are already working on.
<!-- feature-intro-end -->

<!-- feature-section media-size="large" media-shadow="false" -->
## Two-way relationships

Configure a paired Entries field and Many to Many field. When an author changes either side, the plugin updates the same underlying Craft relation on the linked entries.

![An ingredient entry connected to two related recipes.](../screenshots/many-to-many-source.png)

<!-- feature-section-end -->

<!-- feature-section media-size="large" media-shadow="false" -->
## Native Craft relations

The field uses Craft’s entry-selection patterns and stores a Craft relation, so templates, element queries and GraphQL can work with the selected entries using established Craft patterns.

![A recipe entry showing the ingredients related from the other side.](../screenshots/many-to-many-destination.png)
<!-- feature-section-end -->
