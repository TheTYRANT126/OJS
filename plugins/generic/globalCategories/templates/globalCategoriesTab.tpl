{**
 * templates/globalCategoriesTab.tpl
 *
 * Global categories tab in context settings.
 *}
<tab id="globalCategories" label="{translate key="plugins.generic.globalCategories.tabLabel"}">
	{capture assign=globalCategoriesUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.globalCategories.controllers.GlobalCategoriesGridHandler" op="fetchGrid" escape=false}{/capture}
	{load_url_in_div id="globalCategoriesContainer" url=$globalCategoriesUrl}
</tab>
