{**
 * lib/pkp/templates/layouts/backend.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Common site header.
 *}
<!DOCTYPE html>
<html lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{title|strip_tags value=$pageTitle}</title>
	{load_header context="backend"}
	{load_stylesheet context="backend"}
	{load_script context="backend"}
	<link rel="stylesheet" href="{$baseUrl}/amcad-portal/css/backend-header.css">
</head>
<body class="pkp_page_{$requestedPage|escape|default:"index"} pkp_op_{$requestedOp|escape|default:"index"}" dir="{$currentLocaleLangDir|escape|default:"ltr"}">

	<script type="text/javascript">
		// Initialise JS handler.
		$(function() {ldelim}
			$('body').pkpHandler(
				'$.pkp.controllers.SiteHandler',
				{ldelim}
					{include file="controllers/notification/notificationOptions.tpl"}
				{rdelim});
		{rdelim});
	</script>

	{assign var=amcadLang value=$currentLocale|substr:0:2}
	{if $amcadLang == 'en'}
		{assign var=amcadNavInicio value='Home'}
		{assign var=amcadNavLineamientos value='Guidelines'}
		{assign var=amcadNavRecursos value='Resources'}
		{assign var=amcadNavContacto value='Contact'}
		{assign var=amcadNavAcceso value='Access'}
		{assign var=amcadTargetLocale value='es_ES'}
	{else}
		{assign var=amcadNavInicio value='Inicio'}
		{assign var=amcadNavLineamientos value='Lineamientos'}
		{assign var=amcadNavRecursos value='Recursos'}
		{assign var=amcadNavContacto value='Contacto'}
		{assign var=amcadNavAcceso value='Acceso'}
		{assign var=amcadTargetLocale value='en_US'}
	{/if}
	{assign var=amcadPortalBaseUrl value=$baseUrl|cat:'/amcad-portal'}
	{assign var=amcadHomeUrl value=$amcadPortalBaseUrl|cat:'/index.php'}
{capture assign=amcadUserTargetUrl}{url page="admin"}{/capture}
	{if $currentContext}
		{capture assign=amcadUserTargetUrl}{url page="submissions"}{/capture}
	{/if}

	<div class="amcad-backend">
		<header class="main-header" role="banner">
			<div class="container">
				<div class="header-content">
					<div class="header-top-row">
						<a href="{$amcadHomeUrl|escape}" class="header-logo-link">
							<div class="header-logo">
								<img src="{$baseUrl}/amcad-portal/assets/images/AMCAD_logo.png" alt="AMCAD">
							</div>
						</a>
						<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="amcadBackendNav">
							<span class="menu-toggle-bar"></span>
							<span class="menu-toggle-bar"></span>
							<span class="menu-toggle-bar"></span>
						</button>
					</div>

				<nav class="main-nav" id="amcadBackendNav">
					<ul>
						<li><a href="{$amcadPortalBaseUrl|escape}/index.php">{$amcadNavInicio}</a></li>
						<li><a href="{$amcadPortalBaseUrl|escape}/lineamientos.php">{$amcadNavLineamientos}</a></li>
						<li><a href="{$amcadPortalBaseUrl|escape}/recursos.php">{$amcadNavRecursos}</a></li>
						<li><a href="{$amcadPortalBaseUrl|escape}/contacto.php">{$amcadNavContacto}</a></li>
						{if $currentUser}
							<li><a href="{$amcadUserTargetUrl|escape}">{$currentUser->getUsername()|escape}</a></li>
						{else}
							<li><a href="{$baseUrl}/amcad-portal/login.php">{$amcadNavAcceso}</a></li>
						{/if}
						{if $amcadTargetLocale}
							<li class="header-lang-item">
								<a href="{url router=$smarty.const.ROUTE_PAGE page="user" op="setLocale" path=$amcadTargetLocale}" class="lang-link js-backend-lang" data-lang="{$amcadTargetLocale|substr:0:2|lower}" data-setlocale="{url router=$smarty.const.ROUTE_PAGE page="user" op="setLocale" path=$amcadTargetLocale}">
									{$amcadTargetLocale|substr:0:2|upper}
								</a>
							</li>
						{/if}
					</ul>
				</nav>

			</div>
		</div>
		</header>
		<script>
			(function() {
				const root = document.querySelector('.amcad-backend');
				if (!root) return;
				const toggle = root.querySelector('.menu-toggle');
				const nav = root.querySelector('.main-nav');
				if (!toggle || !nav) return;
				toggle.addEventListener('click', function() {
					const isOpen = nav.classList.toggle('is-open');
					toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
				});

				// Sincronizar cookie de idioma del portal con el cambio de locale en backend.
				root.querySelectorAll('.js-backend-lang').forEach(function(link) {
					link.addEventListener('click', function(event) {
						event.preventDefault();
						var langCode = this.dataset.lang || '';
						if (langCode) {
							var expires = new Date();
							expires.setTime(expires.getTime() + 30 * 24 * 60 * 60 * 1000);
							document.cookie = 'amcad_lang=' + langCode + '; path=/; expires=' + expires.toUTCString();
						}
						var target = this.dataset.setlocale || this.getAttribute('href');
						window.location.href = target;
					});
				});
			})();
		</script>
	</div>

	<div id="app" class="app {if $isLoggedInAs} app--isLoggedInAs{/if}">
		<header class="app__header" role="banner">
			{if $availableContexts}
				<dropdown class="app__headerAction app__contexts" v-cloak>
					<template slot="button">
						<icon icon="sitemap"></icon>
						<span class="-screenReader">{translate key="context.contexts"}</span>
					</template>
					<ul>
						{foreach from=$availableContexts item=$availableContext}
							{if !$currentContext || $availableContext->name !== $currentContext->getLocalizedData('name')}
								<li>
									<a href="{$availableContext->url|escape}" class="pkpDropdown__action">
										{$availableContext->name|escape}
									</a>
								</li>
							{/if}
						{/foreach}
					</ul>
				</dropdown>
			{/if}
			{if $currentContext}
				<a class="app__contextTitle" href="{url page="index"}">
					{$currentContext->getLocalizedData('name')|escape}
				</a>
			{elseif $siteTitle}
				<a class="app__contextTitle" href="{$baseUrl}">
					{$siteTitle|escape}
				</a>
			{else}
				<div class="app__contextTitle">
					{translate key="common.software"}
				</div>
			{/if}
			{if $currentUser}
				<div class="app__headerActions" v-cloak>
					{call_hook name="Template::Layout::Backend::HeaderActions"}
					<div class="app__headerAction app__tasks">
						<button ref="tasksButton" @click="openTasks">
							<icon icon="bell-o"></icon>
							<span class="-screenReader">{translate key="common.tasks"}</span>
							<span v-if="unreadTasksCount" class="app__tasksCount">{{ unreadTasksCount }}</span>
						</button>
					</div>
					<dropdown class="app__headerAction app__userNav">
						<template slot="button">
							<icon icon="user-circle-o"></icon>
							{if $isUserLoggedInAs}
								<icon icon="user-circle" class="app__userNav__isLoggedInAsWarning"></icon>
							{/if}
							<span class="-screenReader">{$currentUser->getData('username')}</span>
						</template>
						<nav aria-label="{translate key="common.navigation.user"}">
							{if $supportedLocales|@count > 1}
								<div class="pkpDropdown__section">
									<div class="app__userNav__changeLocale">Change Language</div>
									<ul>
										{foreach from=$supportedLocales item="locale" key="localeKey"}
											<li>
												<a href="{url router=$smarty.const.ROUTE_PAGE page="user" op="setLocale" path=$localeKey}" class="pkpDropdown__action">
													{if $localeKey == $currentLocale}
														<icon icon="check" :inline="true"></icon>
													{/if}
													{$locale|escape}
												</a>
											</li>
										{/foreach}
									</ul>
								</div>
							{/if}
							{if $isUserLoggedInAs}
								<div class="pkpDropdown__section">
									<div class="app__userNav__loggedInAs">
										{translate key="manager.people.signedInAs" username=$currentUser->getData('username')}
										<a href="{url router=$smarty.const.ROUTE_PAGE page="login" op="signOutAsUser"}" class="app__userNav__logOutAs">{translate key="user.logOutAs" username=$currentUser->getData('username')}</a>.
									</div>
								</div>
							{/if}
							<div class="pkpDropdown__section">
								<ul>
									<li v-if="backToDashboardLink">
										<a :href="backToDashboardLink.url" class="pkpDropdown__action">
											{{ backToDashboardLink.name }}
										</a>
									</li>
									<li>
										<a href="{url router=$smarty.const.ROUTE_PAGE page="user" op="profile"}" class="pkpDropdown__action">
											{translate key="user.profile.editProfile"}
										</a>
									</li>
									<li>
										{if $isUserLoggedInAs}
											<a href="{url router=$smarty.const.ROUTE_PAGE page="login" op="signOutAsUser"}" class="pkpDropdown__action">
												{translate key="user.logOutAs" username=$currentUser->getData('username')}
											</a>
										{else}
											<a href="{url router=$smarty.const.ROUTE_PAGE page="login" op="signOut"}" class="pkpDropdown__action">
												{translate key="user.logOut"}
											</a>
										{/if}
									</li>
								</ul>
							</div>
						</nav>
					</dropdown>
				</div>
			{/if}
		</header>

		{* Swap the navigation menu for a back-to-dashboard link when only one item exists *}
		<nav v-if="backToDashboardLink" class="app__returnHeader" aria-label="{translate key="common.navigation.site"}">
			<a class="app__returnHeaderLink" :href="backToDashboardLink.url">
				{{ backToDashboardLabel }}
			</a>
		</nav>

		<div class="app__body">
			{block name="menu"}
				<nav v-if="!!menu && Object.keys(menu).length > 1" class="app__nav" aria-label="{translate key="common.navigation.site"}">
					<ul>
						<li v-for="(menuItem, key) in menu" :key="key" :class="!!menuItem.submenu ? 'app__navGroup' : ''">
							<div v-if="!!menuItem.submenu" class="app__navItem app__navItem--hasSubmenu">
								{{ menuItem.name }}
							</div>
							<a v-else class="app__navItem" :class="menuItem.isCurrent ? 'app__navItem--isCurrent' : ''" :href="menuItem.url">
								{{ menuItem.name }}
							</a>
							<ul v-if="!!menuItem.submenu">
								<li v-for="(submenuItem, submenuKey) in menuItem.submenu" :key="submenuKey">
									<a class="app__navItem" :class="submenuItem.isCurrent ? 'app__navItem--isCurrent' : ''" :href="submenuItem.url">
										{{ submenuItem.name }}
									</a>
								</li>
							</ul>
						</li>
					</ul>
				</nav>
			{/block}

			<main class="app__main">
				<div class="app__page{if $pageWidth} app__page--{$pageWidth}{/if}">
					{block name="breadcrumbs"}
						{if $breadcrumbs}
							<nav class="app__breadcrumbs" role="navigation" aria-label="{translate key="navigation.breadcrumbLabel"}">
								<ol>
									{foreach from=$breadcrumbs item="breadcrumb" name="breadcrumbs"}
										<li>
											{if $smarty.foreach.breadcrumbs.last}
												<span aria-current="page">{$breadcrumb.name|escape}</span>
											{else}
												<a href="{$breadcrumb.url|escape}">
													{$breadcrumb.name|escape}
												</a>
												<span class="app__breadcrumbsSeparator" aria-hidden="true">{translate key="navigation.breadcrumbSeparator"}</span>
											{/if}
										</li>
									{/foreach}
								</ol>
							</nav>
						{/if}
					{/block}

					{block name="page"}{/block}

				</div>
			</main>
		</div>
		<div
			aria-live="polite"
			aria-atomic="true"
			class="app__notifications"
			ref="notifications"
			role="status"
		>
			<transition-group name="app__notification">
				<notification v-for="notification in notifications" :key="notification.key" :type="notification.type" :can-dismiss="true" @dismiss="dismissNotification(notification.key)">
					{{ notification.message }}
				</notification>
			</transition-group>
		</div>
	</div>

	<script type="text/javascript">
		pkp.registry.init('app', {$pageComponent|json_encode}, {$state|json_encode});
	</script>

	<script type="text/javascript">
		// Initialize JS handler
		$(function() {ldelim}
			$('#pkpHelpPanel').pkpHandler(
				'$.pkp.controllers.HelpPanelHandler',
				{ldelim}
					helpUrl: {url|json_encode page="help" escape=false},
					helpLocale: '{$currentLocale|substr:0:2}',
				{rdelim}
			);
		{rdelim});
	</script>
	<div id="pkpHelpPanel" class="pkp_help_panel" tabindex="-1">
		<div class="panel">
			<div class="header">
				<a href="#" class="pkpHomeHelpPanel home">
					{translate key="help.toc"}
				</a>
				<a href="#" class="pkpCloseHelpPanel close">
					{translate key="common.close"}
				</a>
			</div>
			<div class="content">
				{include file="common/loadingContainer.tpl"}
			</div>
			<div class="footer">
				<a href="#" class="pkpPreviousHelpPanel previous">
					{translate key="help.previous"}
				</a>
				<a href="#" class="pkpNextHelpPanel next">
					{translate key="help.next"}
				</a>
			</div>
		</div>
	</div>

</body>
</html>
