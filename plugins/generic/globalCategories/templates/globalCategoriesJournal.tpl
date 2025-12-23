{**
 * templates/globalCategoriesJournal.tpl
 *
 * Display global categories on journal homepage.
 *}
<section class="global-categories">
	<h2 class="global-categories__title">{translate key="plugins.generic.globalCategories.frontend.title"}</h2>
	<ul class="global-categories__list">
		{foreach from=$globalCategories item=category}
			<li class="global-categories__item">{$category.title|escape}</li>
		{/foreach}
	</ul>
</section>
