{**
 * templates/settingsForm.tpl
 *
 * Settings form for global categories.
 *}
<script>
	$(function() {ldelim}
		$('#globalCategoriesSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		$('#addGlobalCategory').on('click', function(e) {ldelim}
			e.preventDefault();
			const templateHtml = $('#globalCategoriesRowTemplate').html();
			$('#globalCategoriesRows').append(templateHtml);
		{rdelim});

		$('#globalCategoriesRows').on('click', '[data-action="remove"]', function() {ldelim}
			$(this).closest('tr').remove();
		{rdelim});
	{rdelim});
</script>

<form class="pkp_form" id="globalCategoriesSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_PAGE page="globalCategories" op="save"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="globalCategoriesSettingsFormNotification"}

	<p class="pkp_help">{translate key="plugins.generic.globalCategories.settings.description"}</p>

	<table class="pkpTable">
		<thead>
			<tr>
				<th>{translate key="plugins.generic.globalCategories.settings.categoryTitle"}</th>
				<th>{translate key="plugins.generic.globalCategories.settings.categoryPathGenerated"}</th>
				<th></th>
			</tr>
		</thead>
		<tbody id="globalCategoriesRows">
			{foreach from=$categories item=category}
				<tr>
					<td>
						<input type="hidden" name="categoryId[]" value="{$category.id|escape}">
						<div class="pkp_formField">
							<label class="pkp_label">
								{translate key="plugins.generic.globalCategories.settings.categoryTitleOriginal" localeName=$primaryLocaleName}
							</label>
							<input type="text" name="categoryTitle[{$primaryLocale}][]" class="pkp_formInput" value="{$category.title[$primaryLocale]|default:''|escape}">
						</div>
						<div class="pkp_formField" style="margin-top: 6px;">
							<label class="pkp_label">
								{translate key="plugins.generic.globalCategories.settings.categoryTitleTranslation" localeName=$translationLocaleName}
							</label>
							<input type="text" name="categoryTitle[{$translationLocale}][]" class="pkp_formInput" value="{$category.title[$translationLocale]|default:''|escape}">
						</div>
					</td>
					<td>
						<span class="pkp_help">{$category.path|escape}</span>
					</td>
					<td>
						<button class="pkp_button" type="button" data-action="remove">{translate key="common.remove"}</button>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
	<script type="text/template" id="globalCategoriesRowTemplate">
		<tr>
			<td>
				<input type="hidden" name="categoryId[]" value="">
				<div class="pkp_formField">
					<label class="pkp_label">
						{translate key="plugins.generic.globalCategories.settings.categoryTitleOriginal" localeName=$primaryLocaleName}
					</label>
					<input type="text" name="categoryTitle[{$primaryLocale}][]" class="pkp_formInput" value="" />
				</div>
				<div class="pkp_formField" style="margin-top: 6px;">
					<label class="pkp_label">
						{translate key="plugins.generic.globalCategories.settings.categoryTitleTranslation" localeName=$translationLocaleName}
					</label>
					<input type="text" name="categoryTitle[{$translationLocale}][]" class="pkp_formInput" value="" />
				</div>
			</td>
			<td>
				<span class="pkp_help">{translate key="plugins.generic.globalCategories.settings.pathWillBeGenerated"}</span>
			</td>
			<td>
				<button class="pkp_button" type="button" data-action="remove">{translate key="common.remove"}</button>
			</td>
		</tr>
	</script>

	<p>
		<button class="pkp_button" type="button" id="addGlobalCategory">
			{translate key="plugins.generic.globalCategories.settings.addCategory"}
		</button>
	</p>

	{fbvFormButtons submitText="common.save"}
</form>
