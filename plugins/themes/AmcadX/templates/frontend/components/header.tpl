{**
 * templates/frontend/components/header.tpl
 *
 * AMCAD X Theme - Header Component
 * Inspired by AMCAD website design
 *
 * @brief Common frontend site header.
 *}

{* Determine whether a logo or title string is being displayed *}
{assign var="showingLogo" value=true}
{if !$displayPageHeaderLogo}
	{assign var="showingLogo" value=false}
{/if}

{capture assign="homeUrl"}
	{url page="index" router=$smarty.const.ROUTE_PAGE}
{/capture}

{* Logo or site title. Only use <h1> heading on the homepage.
	 Otherwise that should go to the page title. *}
{if $requestedOp == 'index'}
	{assign var="siteNameTag" value="h1"}
{else}
	{assign var="siteNameTag" value="div"}
{/if}

{* Determine whether to show a logo of site title *}
{capture assign="brand"}{strip}
	{if $displayPageHeaderLogo}
		<img src="{$publicFilesDir}/{$displayPageHeaderLogo.uploadName|escape:"url"}"
		     {if $displayPageHeaderLogo.altText != ''}alt="{$displayPageHeaderLogo.altText|escape}"
		     {else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if}
				 class="amcad-logo img-fluid">
	{elseif $displayPageHeaderTitle}
		<span class="amcad-site-name">{$displayPageHeaderTitle|escape}</span>
	{else}
		<img src="{$baseUrl}/templates/images/structure/logo.png" alt="{$applicationName|escape}" class="amcad-logo img-fluid">
	{/if}
{/strip}{/capture}

<!DOCTYPE html>
<html lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
{if !$pageTitleTranslated}{capture assign="pageTitleTranslated"}{translate key=$pageTitle}{/capture}{/if}
{include file="frontend/components/headerHead.tpl"}
<body dir="{$currentLocaleLangDir|escape|default:"ltr"}">
<link rel="stylesheet" href="{$baseUrl}/amcad-portal/css/frontend-header.css">
{assign var=amcadLang value=$currentLocale|substr:0:2}
{if $amcadLang == 'en'}
	{assign var=amcadNavInicio value='Home'}
	{assign var=amcadNavLineamientos value='Guidelines'}
	{assign var=amcadNavRecursos value='Resources'}
	{assign var=amcadNavContacto value='Contact'}
	{assign var=amcadNavAcceso value='Login'}
	{assign var=amcadAlternateLocale value='es_ES'}
{else}
	{assign var=amcadNavInicio value='Inicio'}
	{assign var=amcadNavLineamientos value='Lineamientos'}
	{assign var=amcadNavRecursos value='Recursos'}
	{assign var=amcadNavContacto value='Contacto'}
	{assign var=amcadNavAcceso value='Acceso'}
	{assign var=amcadAlternateLocale value='en_US'}
{/if}
{assign var=amcadPortalBaseUrl value=$baseUrl|cat:'/amcad-portal'}
{assign var=amcadHomeUrl value=$amcadPortalBaseUrl|cat:'/index.php'}
{assign var=amcadUserTargetUrl value=$baseUrl|cat:'/index/admin'}
{if $currentContext}
	{assign var=amcadUserTargetUrl value=$baseUrl|cat:'/'|cat:$currentContext->getPath()|cat:'/submissions'}
{/if}

<div class="amcad-portal-shell">
	<header class="main-header" role="banner">
		<div class="container">
			<div class="header-content">
				<div class="header-top-row">
					<a href="{$amcadHomeUrl|escape}" class="header-logo-link">
						<div class="header-logo">
							<img src="{$baseUrl}/amcad-portal/assets/images/AMCAD_logo.png" alt="AMCAD">
						</div>
					</a>
					<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="amcadPortalNav">
						<span class="menu-toggle-bar"></span>
						<span class="menu-toggle-bar"></span>
						<span class="menu-toggle-bar"></span>
					</button>
				</div>

				<nav class="main-nav" id="amcadPortalNav">
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
						{if $amcadAlternateLocale}
							<li class="header-lang-item">
								<a href="{url router=$smarty.const.ROUTE_PAGE page="user" op="setLocale" path=$amcadAlternateLocale}" class="lang-link js-frontend-lang" data-lang="{$amcadAlternateLocale|substr:0:2|lower}" data-setlocale="{url router=$smarty.const.ROUTE_PAGE page="user" op="setLocale" path=$amcadAlternateLocale}">
									{$amcadLang|upper}
								</a>
							</li>
						{/if}
					</ul>
				</nav>

			</div>
		</div>
	</header>
</div>
<script>
	(function() {
		const root = document.querySelector('.amcad-portal-shell');
		if (!root) return;
		const toggle = root.querySelector('.menu-toggle');
		const nav = root.querySelector('.main-nav');
		if (!toggle || !nav) return;
		toggle.addEventListener('click', function() {
			const isOpen = nav.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		});
		root.querySelectorAll('.js-frontend-lang').forEach(function(link) {
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
