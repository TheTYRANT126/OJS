{**
 * templates/adminSettingsPage.tpl
 *
 * Standalone admin page for global categories.
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
	<h1 class="app__pageHeading">{translate key="plugins.generic.globalCategories.adminLink"}</h1>
	{$settingsFormHtml}
{/block}
