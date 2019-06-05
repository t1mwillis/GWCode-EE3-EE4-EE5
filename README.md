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

*category 1
.*subcat 1
..*subsubcat 1

*category 2

*Category group name 2*
*category 5
.*subcat 5
*category 8

**Entry title 2**

*Category group name 1*

*category 1
*category 3

*Category group name 3*

*category 10

### Simple nested categories:

**Entry title 1**

*Category group name 1*

.category 1
.subcat 1
..subsubcat 1
.category 2

*Category group name 2*
.category 5
..subcat 5
.category 8

**Entry title 2**

*Category group name 1*

.category 1
.category 3

*Category group name 3*

.category 10

### Linear categories:
**Entry title 1**
*Category group name 1*
.category 1, subcat 1, subsubcat 1, category 2
*Category group name 2*
.category 5, subcat 5, category 8
**Entry title 2**
*Category group name 1*
.category 1, category 3
*Category group name 3*
.category 10

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
