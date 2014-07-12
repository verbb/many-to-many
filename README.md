##[Craft] Many to Many - < Field Type Plugin >##

###Description###
The Many to Many plugin allows you to manage relationships in Craft from either of the entries that belong to the association. For example, if you have a recipe with many ingredients, and ingredients that belong to many recipes, you can manage the relationship from either the Recipe's entry or the Ingredient's entry.

###Installation###
1. Download the plugin and make sure the parent folder is named "manytomany"
  * manytomany
    * fieldtypes
    * resources
    * services
    * templates
    * ManyToManyPlugin.php
    * README.md
2. Move the folder into your craft/plugins directory
3. Install the plugin from Craft --> System --> Plugins --> Many to Many

###Usage###
1. Create your initial relationship field using the Entries Field Type and attach it to your first section
  * This is done directly through Craft's native "Entries" field type
  * This example assumes this is done on the "Recipes" section creating a field called "Related Ingredients" that allows entries from the Ingredients section
2. Create another field that attaches the relationship using the Many to Many field type
  * This is done by creating a new field and selecting the Many to Many field type
3. Customize your Settings
  * *Linked Section* will be the initial section that contains the relationship. (in our example Recipes)
  * *Associated Field* is the field on the other end of this relationship. (in our example Related Ingredients)
4. Attach the newly created Many to Many field to your section

###To Do###
1. Allow the field to work across all (or at least more) Element Types. Currently only supports Entries
2. Allow custom ordering of the secondary relationship. Currently you can only order the primary relationship (by Craft's native ordering of the Entries field type)
3. Other stuff I assume.

###Version History###
* 0.1 - Initial Release
