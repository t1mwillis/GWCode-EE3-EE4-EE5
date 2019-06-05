# GWCode-EE3-EE4-EE5
A fork of the GWCode module to support EE3+ as well as PHP7

## Example 1 - Showing categories for an entry
Showing categories for an entry is easy, you can do this with ExpressionEngine's native {categories} variable pair: http://expressionengine.com/user_guide/modules/channel/channel_entries.html#categories

Or, with GWcode Categories:

```
<ul>
{exp:gwcode_categories entry_id="19" style="linear"}
    <li>{cat_name}</li>
{/exp:gwcode_categories}
</ul>
```

Now, something that can't be done with ExpressionEngine's native {categories} variable pair. Let's say you have a channel with multiple category groups. Those category groups all have their own categories. When you create a new entry for that channel, you add it to any number of categories from any of those category groups. You'd like to list the category groups and categories the entry has been added to. Nested, or linear:

### Nested categories:

**Entry title 1**

*Category group name 1*

* category 1
  * subcat 1
    * subsubcat 1

* category 2

*Category group name 2*

* category 5
  * subcat 5
* category 8

**Entry title 2**

*Category group name 1*

* category 1
* category 3

*Category group name 3*

 * category 10

### Simple nested categories:

**Entry title 1**

*Category group name 1*

 * category 1
   * subcat 1
     * subsubcat 1
 * category 2

*Category group name 2*

 * category 5
   * subcat 5
 * category 8

**Entry title 2**

*Category group name 1*

 * category 1
 * category 3

*Category group name 3*

 * category 10

### Linear categories:
**Entry title 1**

*Category group name 1*

 * category 1, subcat 1, subsubcat 1, category 2
 
*Category group name 2*

 * category 5, subcat 5, category 8
 
**Entry title 2**

*Category group name 1*

 * category 1, category 3
 
*Category group name 3*

 * category 10

With standard ExpressionEngine tags this is impossible to accomplish, unless you use {exp:query} tags in your templates.
Here's the code you'd use with GWcode Categories:

### Nested Categories:
```
{exp:channel:entries channel="example" disable="categories|member_data|pagination"}
	<b>{title}</b><br />
	{exp:gwcode_categories entry_id="{entry_id}"}
		{group_heading}{cat_group_name}{/group_heading}
		{cat_name}
	{/exp:gwcode_categories}
{/exp:channel:entries}
```

### Simple Nested Categories:
```
{exp:channel:entries channel="example" disable="categories|member_data|pagination"}
	<b>{title}</b><br />
	{exp:gwcode_categories entry_id="{entry_id}" style="simple"}
		{group_heading}{cat_group_name}{/group_heading}
		{cat_name}
	{/exp:gwcode_categories}
{/exp:channel:entries}
```

### Linear Categories:
```
{exp:channel:entries channel="example" disable="categories|member_data|pagination"}
	<b>{title}</b>
	{exp:gwcode_categories entry_id="{entry_id}" style="linear" backspace="1"}
		{if group_start}<br />{cat_group_name}<br />{/if}
		{cat_name},
	{/exp:gwcode_categories}
	<br /><br />
{/exp:channel:entries}
```

You could also use the last_only parameter to show only last child categories, or the depth, min_depth or max_depth parameters to only show categories with a certain depth.

## Example 2 - Showing last child categories only
The `last_only` parameter can be used to display last child categories only.
My "example" channel has been assigned a category group with the following categories:

* cat1
  * cat1_1
    * cat1_1_1
    * cat1_1_2
      * cat1_1_2_1
  * cat1_2
* cat2
  * cat2_1
  
To get the last child categories only (highlighted in blue) for this channel, the following code can be used:

```
{exp:gwcode_categories channel="example" last_only="yes"}
	{cat_name}
{/exp:gwcode_categories}
```

..which will return a simple nested list of last child categories:

* cat1_1_1
* cat1_1_2_1
* cat1_2
* cat2_1

Of course, you can also show the categories in a linear form by using the `style="linear"` parameter.

If you'd like to show last child categories for an entry, use the `entry_id` instead of the `channel` parameter:

```
{exp:gwcode_categories entry_id="20" last_only="yes"}
	{cat_name}
{/exp:gwcode_categories}
```

Or last child categories for certain category groups:

```
{exp:gwcode_categories group_id="1|2|3" last_only="yes"}
	{cat_name}
{/exp:gwcode_categories}
```

You can also use a category as a starting point. For example, if you want to show last child categories with cat1 in the example above as the starting point, it will return cat1_1_1, cat1_1_2_1 and cat1_2:

```
{exp:gwcode_categories cat_id="13" last_only="yes"}
	{cat_name}
{/exp:gwcode_categories}
```

## Example 3 - Showing categories of any (fixed, minimum or maximum) depth
> Selecting categories by depth has a huge advantage over selecting categories by ID as you would have to do with the standard EE tags, because it allows you to easily add or remove categories in the control panel whithout ever having to update your templates with the new category ID's to reflect the changes!

In this example, we are going to get the categories with a fixed depth of 1 or 2 and categories with a minimum depth of 4. They are **in bold text**:

* **cat1** (depth: 1)
  * **cat1_1** (depth: 2)
    * cat1_1_1 (depth: 3)
    * cat1_1_2 (depth: 3)
      * **cat1_1_2_1** (depth: 4)
        * **cat1_1_2_1_1** (depth: 5)
  * **cat1_2** (depth: 2)
* **cat2** (depth: 1)
  * **cat2_1** (depth: 2)
  
The code for a nested list of categories:
```
{exp:gwcode_categories channel="example" depth="1|2" min_depth="4"}
	{cat_name}
{/exp:gwcode_categories}
```
Or, for a comma seperated (linear) list:
```
{exp:gwcode_categories channel="example" depth="1|2" min_depth="4" style="linear" backspace="1"}
	{cat_name},
{/exp:gwcode_categories}
```
Alternatively, this would work as well (using conditionals instead of the `backspace` parameter):
```
{exp:gwcode_categories channel="example" depth="1|2" min_depth="4" style="linear"}
	{if cat_count == results_total}
		{cat_name}
	{if:else}
		{cat_name},
	{/if}
{/exp:gwcode_categories}
```
As always, you could also use the `entry_id` or `group_id` parameter instead of the `channel` parameter to show categories for an entry or category group(s) respectively.

## Example 4 - Showing the entry count for categories
Version 1.1 of GWcode Categories introduced a new variable called `entry_count`, which you can use to show the number of entries in a category. Using this variable, it's easy to create a navigation menu with the number of entries next to each category:

* Category 1 (20)
* Category 2 (14)
* Category 3 (8)

The code below will create the menu and show the total number of entries in the categories:
```
{exp:gwcode_categories channel="example" entry_count="yes"}
	<a href="{path="examplepath/{cat_url_title}"}">{cat_name}</a> ({entry_count})
{/exp:gwcode_categories}
```
To only show the number of entries with a certain status, you can use the `status` parameter. If you use the `status` parameter, the `entry_count` parameter will effectively be set to "yes", so you don't need to add that one:
```
{exp:gwcode_categories channel="example" status="open|approved"}
	<a href="{path="examplepath/{cat_url_title}"}">{cat_name}</a> ({entry_count})
{/exp:gwcode_categories}
```
Version 1.1 of the plugin also introduced the `show_empty` parameter. It allows you to show only categories with entries (optionally with a certain status):
```
{exp:gwcode_categories channel="example" status="open" show_empty="no"}
	<a href="{path="examplepath/{cat_url_title}"}">{cat_name}</a> ({entry_count})
{/exp:gwcode_categories}
```
Here's an example of how you select a category's children categories and then also show their entry count:
```
{exp:gwcode_categories channel="example" status="open" cat_id="12"}
	<a href="{path="examplepath/{cat_url_title}"}">{cat_name}</a> ({entry_count})
{/exp:gwcode_categories}
```

## Example 5 - Category based breadcrumbs

In this example, I'm creating a breadcrumb trail for an entry. The entry has been assigned to two categories: "ExpressionEngine" and its child category "GWcode Categories", which both belong to a category group named "Add-ons".

I want to be able to create a trail which looks like this: [Home] » [Add-ons] » [ExpressionEngine] » [GWcode Categories] » [Overview].

This is what I would use in my template for the entry page:
```
{exp:channel:entries channel="add-ons" limit="1"}
	<a href="{site_url}">Home</a> &raquo;
	{exp:gwcode_categories entry_id="{entry_id}" style="linear"}
		{if group_start}<a href="{path="add-ons"}">{cat_group_name}</a> &raquo;{/if}
		<a href="{path="add-ons/{cat_url_title}"}">{cat_name}</a> &raquo;
	{/exp:gwcode_categories}
	{title}
{/exp:channel:entries}
```

## Example 6 - Automatic nested numbering

With a couple lines of CSS code, we can get a list of categories with automatic nested numbering (sub-numbering for child categories) such as this:

1. cat1
  1. cat1_1
    1. cat1_1_1
    2. cat1_1_2
      1. cat1_1_2_1
  2. cat1_2
2. cat2
  1. cat2_1
  
The CSS:
```
ol.subcount, ol.subcount ol { counter-reset: item; }
ol.subcount li {
	display: block;
	margin: 0;
	padding: 0;
	font-weight: bold;
}
ol.subcount li ol li {
	display: block;
	margin-left: 25px;
}
ol.subcount li:before {
	content: counters(item, ".") ") ";
	counter-increment: item;
}
```
The plugin code:
```
{exp:gwcode_categories channel="example" list_type="ol" class="subcount"}
	<span style="font-weight: normal;">{cat_name}</span>
{/exp:gwcode_categories}
```

## Example 7 - Counting categories
This plugin can also be used to count the number of categories. A couple of examples:

Total number of categories, overall:
```
{exp:gwcode_categories limit="1"}
	Total: {results_total}
{/exp:gwcode_categories}
```
Total number of last child categories for channel "example":
```
{exp:gwcode_categories channel="example" last_only="yes" limit="1"}
	Total: {results_total}
{/exp:gwcode_categories}
```
Total number of depth 2 categories an entry has been added to:
```
{exp:gwcode_categories entry_id="30" depth="2" limit="1"}
	Total: {results_total}
{/exp:gwcode_categories}
```
