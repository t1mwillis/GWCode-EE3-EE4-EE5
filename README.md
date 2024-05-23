# Expression Engine GWCode (Deprecated)
A fork of the GWCode module to support EE3-EE7 as well as PHP8.2. All code is the work of Leon Dijk (GWcode).

This repository is no longer actively maintained -- for more "up-to-date" code, please go [here to @ignetic's fork](https://github.com/ignetic/GWCode-EE3-EE4-EE5).

## To Install

Add the `gwcode-categories` folder to your system > users > addons folder, then click install in the Expression Engine Control Panel.

## Documentation

* [Example 1 - Showing categories for an entry](#example-1---showing-categories-for-an-entry)
* [Example 2 - Showing last child categories only](#example-2---showing-last-child-categories-only)
* [Example 3 - Showing categories of any (fixed, minimum or maximum) depth](#example-3---showing-categories-of-any-fixed-minimum-or-maximum-depth)
* [Example 4 - Showing the entry count for categories](#example-4---showing-the-entry-count-for-categories)
* [Example 5 - Category based breadcrumbs](#example-5---category-based-breadcrumbs)
* [Example 6 - Automatic nested numbering](#example-6---automatic-nested-numbering)
* [Example 7 - Counting categories](#example-7---counting-categories)
* [Example 8 - Conditionals](#example-8---conditionals)
* [Example 9 - Showing child categories or parent categories](#example-9---showing-child-categories-or-parent-categories)
* [Example 10 - Sorting categories](#example-10---sorting-categories)
* [Example 11 - Creating a menu with specific code](#example-11---creating-a-menu-with-specific-code)

---

### Example 1 - Showing categories for an entry
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

#### Nested categories:

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

#### Simple nested categories:

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

#### Linear categories:

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

#### Nested Categories:
```
{exp:channel:entries channel="example" disable="categories|member_data|pagination"}
	<b>{title}</b><br />
	{exp:gwcode_categories entry_id="{entry_id}"}
		{group_heading}{cat_group_name}{/group_heading}
		{cat_name}
	{/exp:gwcode_categories}
{/exp:channel:entries}
```

#### Simple Nested Categories:
```
{exp:channel:entries channel="example" disable="categories|member_data|pagination"}
	<b>{title}</b><br />
	{exp:gwcode_categories entry_id="{entry_id}" style="simple"}
		{group_heading}{cat_group_name}{/group_heading}
		{cat_name}
	{/exp:gwcode_categories}
{/exp:channel:entries}
```

#### Linear Categories:
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

### Example 2 - Showing last child categories only
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

### Example 3 - Showing categories of any (fixed, minimum or maximum) depth
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

### Example 4 - Showing the entry count for categories
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

### Example 5 - Category based breadcrumbs

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

### Example 6 - Automatic nested numbering

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

### Example 7 - Counting categories
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

### Example 8 - Conditionals

Here's an example to show how to use conditionals:
```
{exp:gwcode_categories group_id="3|4"}
	{group_heading}<b>{cat_group_name}</b><br />{/group_heading}
	{if cat_group_name == "Blog"}
		<a href="{path="blog/{cat_url_title}"}">{cat_name}</a>
	{if:else}
		{if depth == 2}
			<a href="{path="add-ons/{cat_url_title}"}">{cat_name}</a>
		{if:else}
			{cat_name}
		{/if}
	{/if}
{/exp:gwcode_categories}
```

### Example 9 - Showing child categories or parent categories

Here's the category group I'm going to use for these examples:

* **cat1**
  * cat1_1
    * cat1_1_1
    * cat1_1_2
      * cat1_1_2_1
  * cat1_2
* cat2
  * cat2_1

**Child categories**
Getting child categories for a category is easy. To get the child categories for **cat1**, you'll only have to provide the `cat_id` parameter with the category ID (13 in my case):
```
{exp:gwcode_categories cat_id="13"}
	{cat_name}
{/exp:gwcode_categories}
```
Or, you can use the cat_url_title parameter. Let's say that your URL looks like this:
[http://domain.com/group/template/services]
where "services" is the category (segment_3). Your code could then be:
```
{exp:gwcode_categories cat_url_title="{segment_3}"}
	{cat_name}
{/exp:gwcode_categories}
```
The results will be a list like this:

* cat1
  * cat1_1
    * cat1_1_1
    * cat1_1_2
     * cat1_1_2_1
  * cat1_2
  
If you don't want to show the "cat1" category, you can add the `incl_self` parameter like so: `incl_self="no"`. And, if you don't want to show the "cat1_2" category for example, you can use the `excl_cat_id` parameter to remove it. As always, you can use the `depth`, `min_depth` or `max_depth` parameters as well, to only show those categories with a certain depth.

> If you're using the `cat_url_title` parameter and 2 or more category groups have a category with the same category url title, you may want to add in the `group_id` parameter to make sure the plugin isn't selecting the category from the wrong category group.

**Parent categories**
Getting a category's parent categories is just as easy. To get the parent categories for cat1_1_2_1, you use the `cat_id` or `cat_url_title` parameter in combination with the `show_trail` parameter like so (where 26 is my ID for cat1_1_2_1):
```
{exp:gwcode_categories cat_id="26" show_trail="yes"}
	{cat_name}
{/exp:gwcode_categories}
```
The results will look like this:

* cat1
  * cat1_1
    * cat1_1_2
      * cat1_1_2_1
      
In most cases, you'll probably want to use this to create a breadcrumb trail from the root category to the category you provide. So, you'll want to use the `style` parameter to make the list linear and the `backspace` parameter to remove the last divider:
```
{exp:gwcode_categories cat_id="26" show_trail="yes" style="linear" backspace="7"}
	{cat_name} &raquo;
{/exp:gwcode_categories}
```
This will then be the output:

cat1 » cat1_1 » cat1_1_2 » cat1_1_2_1

### Example 10 - Sorting categories

In this example, I'm going to show you how to grab a list of categories and then sort them to show a
"Newest categories" list and a "Most popular categories" list.

To get the list I want, I'm going to use the code below. It gets the categories with depth 3 or 4 in category group 5 and shows the number of entries for those categories:
```
{exp:gwcode_categories group_id="5" depth="3|4" entry_count="yes"}
	{cat_name} ({entry_count})
{/exp:gwcode_categories}
```
The output looks like this:

* E-Commerce (1)
  * Magento (2)
* Content Management System (1)
  * ExpressionEngine (4)
  * Wordpress (0)
  
**Newest categories, sorting them by `cat_id`** 

To re-order the list above so that the categories which have been added the latest are at the top, you can use the `orderby` and `sort` parameters. For the `orderby` parameter, I'm going to use "cat_id", since the categories you've added latest will have the highest category ID. For the `sort` parameter, I'm going to use "desc" as the categories with the highest category ID's should be at the top. For clarification, I've also added the `{cat_id}` variable in the output so you can see how the list is being sorted.
```
{exp:gwcode_categories group_id="5" depth="3|4" entry_count="yes" orderby="cat_id" sort="desc"}
	{cat_name} ({entry_count}) &larr; ID: {cat_id}
{/exp:gwcode_categories}
```
The output will then be:

* Wordpress (0) ← ID: 67
* ExpressionEngine (4) ← ID: 65
* E-commerce (1) ← ID: 64
* Content Management System (1) ← ID: 22
* Magento (2) ← ID: 20

**Most popular categories, sorting them by `entry_count` and `cat_name`**

If you want to show a most popular categories list, which has the categories with the most entries at the top, you'll have to use "entry_count" for the `orderby` parameter and "desc" for the `sort` parameter. If you also want to make sure that categories with the same number of entries are sorted alphabatically (A at the top, Z at the bottom), you can add "cat_name" as a second value for the `orderby` parameter and "asc" as a second value for the `sort` parameter like so:
```
{exp:gwcode_categories group_id="5" depth="3|4" entry_count="yes" orderby="entry_count|cat_name" sort="desc|asc"}
	{cat_name} ({entry_count}) &larr; ID: {cat_id}
{/exp:gwcode_categories}
```
..making the output look like this:

* ExpressionEngine (4) ← ID: 65
* Magento (2) ← ID: 20
* Content Management System (1) ← ID: 22
* E-commerce (1) ← ID: 64
* Wordpress (0) ← ID: 67

As you can see, Content Management System and E-commerce have the same number of entries (1) and Content Management System is listed first because of the second parameter values for the `orderby` and `sort` parameters (cat_name, asc).

### Example 11 - Creating a menu with specific code

GWcode Categories v1.8.0 introduced a couple of new variables. Some of them were added to be able to create complete category based navigation menus which sometimes may require distinct HTML/CSS code.

For example, you might need to create the following code to generate your menu:
```
<div class="dropdown">
	<h2>Services</h2>
	<div class="group">
		<div class="col">
			<h3><a href="/services/detail/cms">CMS</a></h3>
			<ul>  
				<li><a href="/services/detail/cms/expressionengine">ExpressionEngine</a></li>
				<li><a href="/services/detail/cms/wordpress">Wordpress</a></li>
				<li><a href="/services/detail/cms/joomla">Joomla</a></li>
				<li><a href="/services/detail/cms/drupal">Drupal</a></li>
			</ul>
		</div>
		<div class="col">
			<h3><a href="/services/detail/design">Design</a></h3>
			<ul>  
				<li><a href="/services/detail/design/websites">Websites</a></li>
				<li><a href="/services/detail/design/print">Print</a></li>
				<li><a href="/services/detail/design/logo">Logo</a></li>
			</ul>
		</div>
		<div class="col">
			<h3><a href="/services/detail/e-commerce">E-Commerce</a></h3>
			<ul>  
				<li><a href="/services/detail/e-commerce/magento">Magento</a></li>
				<li><a href="/services/detail/e-commerce/brilliantretail">BrilliantRetail</a></li>
				<li><a href="/services/detail/e-commerce/cartthrob">CartThrob</a></li>
				<li><a href="/services/detail/e-commerce/oscommerce">osCommerce</a></li>
				<li><a href="/services/detail/e-commerce/virtuemart">VirtueMart</a></li>
				<li><a href="/services/detail/e-commerce/zen-cart">Zen Cart</a></li>
			</ul>
		</div>
    </div>
	<div class="group">
		<div class="col">
			<h3><a href="/services/detail/javascript">Javascript</a></h3>
			<ul>  
				<li><a href="/services/detail/javascript/jquery">jQuery</a></li>
				<li><a href="/services/detail/javascript/prototype">Prototype</a></li>
			</ul>
		</div>
		<div class="col">
			<h3><a href="/services/detail/seo">SEO</a></h3>
			<ul>  
				<li><a href="/services/detail/seo/sitemaps">Sitemaps</a></li>
				<li><a href="/services/detail/seo/consult">Consult</a></li>
				<li><a href="/services/detail/seo/writing">Writing</a></li>
			</ul>
		</div>
		<div class="col">
			<h3><a href="/services/detail/hosting">Hosting</a></h3>
			<ul>  
				<li><a href="/services/detail/hosting/linux">Linux</a></li>
				<li><a href="/services/detail/hosting/windows">Windows</a></li>
				<li><a href="/services/detail/hosting/backups">Backups</a></li>
				<li><a href="/services/detail/hosting/dns">DNS</a></li>
			</ul>
		</div>
	</div>
</div>
```

The category group that has been created in ExpressionEngine has 6 root categories (depth 1), which all have a number of child categories (depth 2).

Looking at the code above, there are a couple of "problems" we need to solve:

1. Each div tag with a "col" class consists of a root category wrapped in h3 tags;
2. We need to make sure that the first three and last three "col" divs are both wrapped in a div tag with a "group" class;
3. An unordered list with child categories as list items needs to be placed in each div tag with a "col" class;
4. The parents' `url_title` is required in the hyperlink path for the child categories.

**Getting started**

First we need to determine what exactly should be part of the plugin output. In this case, the div tags with a "group" class still needs to be part of the plugin output since there are two of those and we need to show some of the categories in one and some categories in the other. Anything before and after that can be placed outside the plugin:
```
<div class="dropdown">
<h2>Services</h2>
{exp:gwcode_categories channel="services" style="linear"}
	..
{/exp:gwcode_categories}
</div>
```
By using the `style="linear"` parameter, we have more control on what the output should look like.

**Problem 1 - Creating "col" class div tags with root categories**

Now that the basics has been setup, it's time to use the plugin, which loops through the categories one by one, starting with "CMS" and ending with "SEO". The "col" blocks with root (depth 1) categories wrapped in h3 tags can be created by using the {depth1_start} and {depth1_end} variables:
```
<div class="dropdown">
<h2>Services</h2>
{exp:gwcode_categories channel="services" style="linear"}
	{if depth1_start}
				<div class="col">
					<h3><a href="#">{cat_name}</a></h3>
	{/if}
	{if depth1_end}
				</div>
	{/if}
{/exp:gwcode_categories}
</div>
```
This will create the following output:
```
<div class="dropdown">
	<h2>Services</h2>
		<div class="col">
			<h3><a href="#">CMS</a></h3>
		</div>
		<div class="col">
			<h3><a href="#">Design</a></h3>
		</div>
		<div class="col">
			<h3><a href="#">E-Commerce</a></h3>
		</div>
		<div class="col">
			<h3><a href="#">Javascript</a></h3>
		</div>
		<div class="col">
			<h3><a href="#">SEO</a></h3>
		</div>
		<div class="col">
			<h3><a href="#">Hosting</a></h3>
		</div>
</div>
```
**Problem 2 - Splitting six div tags with "col" class into two groups of three**

For this, we can use the depth1_start_count and depth1_end_count variables:
```
<div class="dropdown">
<h2>Services</h2>
{exp:gwcode_categories channel="services" style="linear"}
	{if depth1_start AND depth1_start_count == 1}
			<div class="group">
	{/if}
	{if depth1_start}
				<div class="col">
					<h3><a href="#">{cat_name}</a></h3>
	{/if}
	{if depth1_end}
				</div>
	{/if}
	{if depth1_end AND depth1_end_count == 3}
			</div>
			<div class="group">
	{/if}
	{if depth1_end AND depth1_end_count == 6}
			</div>
	{/if}
{/exp:gwcode_categories}
</div>
```
See how this works? Lines 04-06 create a new div tag with "group" class before the first root (depth 1) category is being added to the output. Lines 14-17 close the first div tag with "group" class and creates a second one after the 3rd root (depth 1) category has been added to the output. Lines 18-20 closes the second div tag with "group" class after the 6th root (depth 1) category has been added to the output, making it now look like this:
```
<div class="dropdown">
	<h2>Services</h2>
	<div class="group">
		<div class="col">
			<h3><a href="#">CMS</a></h3>
		</div>
		<div class="col">
			<h3><a href="#">Design</a></h3>
		</div>
		<div class="col">
			<h3><a href="#">E-Commerce</a></h3>
		</div>
	</div>
	<div class="group">
		<div class="col">
			<h3><a href="#">Javascript</a></h3>
		</div>
		<div class="col">
			<h3><a href="#">SEO</a></h3>
		</div>
		<div class="col">
			<h3><a href="#">Hosting</a></h3>
		</div>
	</div>
</div>
```
**Problem 3 - Adding the child categories**

Since the (depth 2) child categories are shown in an unordered list, we need to add the opening ul tag when the output for depth 2 categories starts. Likewise, the ul tag needs to be closed when the output for depth 2 categories ends. And, in between, we check if the category has a depth of 2 and if so, add it to the output wrapped in li tags. This gives us the following code, which can be added right between the code we've created for the opening div tag with "col" class and its closing div tag:
```
{if depth2_start}
				<ul>
{/if}
{if depth == 2}
					<li><a href="#">{cat_name}</a></li>
{/if}
{if depth2_end}
				</ul>
{/if}
```
**Problem 4 - Creating the hyperlinks**

This one is actually really easy. GWcode Categories has a variable called `{complete_path}` which creates a path to the category starting with the root category as defined in your category group. So, for the "DNS" category, it will parse "hosting/dns", which is exactly what we need ("/services/detail/" can be used as a static prefix as it will always be the same for all categories).

Putting it all together
So there you have it, a category based navigation menu with the ability to add or remove child categories or rearrange them:
```
<div class="dropdown">
<h2>Services</h2>
{exp:gwcode_categories channel="services" style="linear"}
	{if depth1_start AND depth1_start_count == 1}
			<div class="group">
	{/if}
	{if depth1_start}
				<div class="col">
					<h3><a href="/services/detail/{complete_path}">{cat_name}</a></h3>
	{/if}
	{if depth2_start}
					<ul>
	{/if}
	{if depth == 2}
						<li><a href="/services/detail/{complete_path}">{cat_name}</a></li>
	{/if}
	{if depth2_end}
					</ul>
	{/if}
	{if depth1_end}
				</div>
	{/if}
	{if depth1_end AND depth1_end_count == 3}
			</div>
			<div class="group">
	{/if}
	{if depth1_end AND depth1_end_count == 6}
			</div>
	{/if}
{/exp:gwcode_categories}
</div>
```
