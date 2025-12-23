{**
 * templates/globalCategoriesContextForm.tpl
 *
 * Form for assigning global categories to a context.
 *}
<script>
	$(function() {ldelim}
		const $form = $('#globalCategoriesContextForm');
		const $selectedList = $('#globalCategoriesSelected');
		const $availableList = $('#globalCategoriesAvailable');
		const orderControlsHtml = '<button class="pkp_button" type="button" data-action="move-up">↑</button><button class="pkp_button" type="button" data-action="move-down">↓</button>';
		$form.pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		$form.on('formSubmitted', function() {ldelim}
			if ($form.data('reloadAfterSubmit')) {ldelim}
				window.location.reload();
			{rdelim}
		{rdelim});

		$('#addGlobalCategoryRow').on('click', function(e) {ldelim}
			e.preventDefault();
			$('#globalCategoriesNewSection').prop('hidden', false);
			$('#globalCategoriesNewInput').focus();
			$(this).prop('disabled', true);
		{rdelim});

		$('#globalCategoriesSaveNew').on('click', function() {ldelim}
			$form.data('reloadAfterSubmit', true);
		{rdelim});

		$form.on('change', '.globalCategoryToggle', function() {ldelim}
			const $checkbox = $(this);
			const $item = $checkbox.closest('li');

			if ($checkbox.is(':checked')) {ldelim}
				if (!$item.find('input[name="globalCategoryIds[]"]').length) {ldelim}
					$item.append('<input type="hidden" name="globalCategoryIds[]" value="' + $item.data('categoryId') + '">');
				{rdelim}
				if (!$item.find('[data-action="move-up"]').length) {ldelim}
					const $controls = $(orderControlsHtml);
					$checkbox.closest('label').after($controls);
				{rdelim}
				$selectedList.append($item);
			{rdelim} else {ldelim}
				$item.find('input[name="globalCategoryIds[]"]').remove();
				$item.find('[data-action="move-up"], [data-action="move-down"]').remove();
				$availableList.append($item);
			{rdelim}
		{rdelim});

		$form.on('click', '[data-action="move-up"]', function() {ldelim}
			const $item = $(this).closest('li');
			const $prev = $item.prev('li');
			if ($prev.length) {ldelim}
				$prev.before($item);
			{rdelim}
		{rdelim});

		$form.on('click', '[data-action="move-down"]', function() {ldelim}
			const $item = $(this).closest('li');
			const $next = $item.next('li');
			if ($next.length) {ldelim}
				$next.after($item);
			{rdelim}
		{rdelim});
	{rdelim});
</script>
<style>
	.globalCategoriesList li {
		display: flex;
		align-items: center;
		gap: 6px;
		margin-bottom: 6px;
	}
	.globalCategoriesList [data-action="move-up"],
	.globalCategoriesList [data-action="move-down"] {
		padding: 0 6px;
		line-height: 1.1;
		min-height: 24px;
	}
	.globalCategoriesList label {
		margin: 0;
	}
</style>

<form class="pkp_form" id="globalCategoriesContextForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.globalCategories.controllers.GlobalCategoriesGridHandler" op="save"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="globalCategoriesContextFormNotification"}

	<p class="pkp_help">{translate key="plugins.generic.globalCategories.context.description"}</p>

	{if $errorMessage}
		<div class="pkp_form_error">{$errorMessage|escape}</div>
	{/if}

	{if $categories|@count}
		<ul class="pkp_formList globalCategoriesList" id="globalCategoriesSelected">
			{foreach from=$categories item=category}
				{if in_array($category.id, $selectedCategoryIds)}
					<li data-category-id="{$category.id|escape}">
						<label>
							<input type="checkbox" class="globalCategoryToggle" checked>
						</label>
						<button class="pkp_button" type="button" data-action="move-up">↑</button>
						<button class="pkp_button" type="button" data-action="move-down">↓</button>
						{$category.titleLocalized|escape}
						<input type="hidden" name="globalCategoryIds[]" value="{$category.id|escape}">
					</li>
				{/if}
			{/foreach}
		</ul>
		<hr style="margin: 12px 0;">
		<ul class="pkp_formList globalCategoriesList" id="globalCategoriesAvailable">
			{foreach from=$categories item=category}
				{if !in_array($category.id, $selectedCategoryIds)}
					<li data-category-id="{$category.id|escape}">
						<label>
							<input type="checkbox" class="globalCategoryToggle">
						</label>
						{$category.titleLocalized|escape}
					</li>
				{/if}
			{/foreach}
		</ul>
	{else}
		<p class="pkp_help">{translate key="plugins.generic.globalCategories.context.none"}</p>
	{/if}

	<h3 class="pkp_help">{translate key="plugins.generic.globalCategories.context.addNewTitle"}</h3>
	<p class="pkp_help">{translate key="plugins.generic.globalCategories.context.addNewDescription"}</p>
	<div id="globalCategoriesNewSection" hidden>
		<table class="pkpTable">
			<thead>
				<tr>
					<th>{translate key="plugins.generic.globalCategories.settings.categoryTitle"}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<input type="text" id="globalCategoriesNewInput" name="newCategoryTitle[]" class="pkp_formInput" value="" />
					</td>
					<td>
						<button class="pkp_button" type="submit" id="globalCategoriesSaveNew">
							{translate key="common.save"}
						</button>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<p>
		<button class="pkp_button" type="button" id="addGlobalCategoryRow">
			{translate key="plugins.generic.globalCategories.context.addNew"}
		</button>
	</p>

	{fbvFormButtons}
</form>
