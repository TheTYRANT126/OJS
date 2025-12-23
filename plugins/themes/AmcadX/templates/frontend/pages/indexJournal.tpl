{**
 * templates/frontend/pages/indexJournal.tpl
 *
 * AMCAD X Theme - Journal Index Page
 * Inspired by UNAM catalog design
 *
 * @brief Display the index page for a journal with AMCAD styling
 *
 * @uses $currentJournal Journal This journal
 * @uses $journalDescription string Journal description from HTML text editor
 * @uses $homepageImage object Image to be displayed on the homepage
 * @uses $additionalHomeContent string Arbitrary input from HTML text editor
 * @uses $announcements array List of announcements
 * @uses $numAnnouncementsHomepage int Number of announcements to display on the homepage
 * @uses $issue Issue Current issue
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$currentJournal->getLocalizedName()}

{* Hero Section - AMCAD Style *}
<div class="amcad-hero">
	<div class="container">
		<div class="row align-items-center">
			<div class="col-lg-8">
				<h1 class="amcad-hero-title">{$currentJournal->getLocalizedName()|escape}</h1>
				{if $journalDescription}
					<div class="amcad-hero-description">
						{$journalDescription|strip_unsafe_html|truncate:300}
					</div>
				{/if}
			</div>
			{if $homepageImage}
				<div class="col-lg-4">
					<div class="amcad-hero-image">
						<img src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}"
						     alt="{$homepageImageAltText|escape}"
						     class="img-fluid rounded shadow">
					</div>
				</div>
			{/if}
		</div>
	</div>
</div>

<div class="container amcad-main-content">

	{* Current Issue Catalog - UNAM Style *}
	{if $issue}
		<section class="amcad-current-issue">
			<div class="amcad-section-header text-center">
				<h2 class="amcad-section-title">{translate key="journal.currentIssue"}</h2>
				<div class="amcad-section-subtitle">{$issue->getIssueSeries()|escape}</div>
				<p class="amcad-issue-published">
					{translate key="plugins.themes.AmcadX.currentIssuePublished" date=$issue->getDatePublished()|date_format:$dateFormatLong}
				</p>
			</div>

			<div class="row justify-content-center amcad-issue-showcase">
				{if $issue->getLocalizedCoverImageUrl()}
					<div class="col-lg-4 col-md-5 mb-4">
						<div class="amcad-issue-cover-wrapper">
							<a href="{url op="view" page="issue" path=$issue->getBestIssueId()}">
								<img class="img-fluid amcad-issue-cover shadow-lg"
								     src="{$issue->getLocalizedCoverImageUrl()|escape}"
								     {if $issue->getLocalizedCoverImageAltText() != ''}
								     alt="{$issue->getLocalizedCoverImageAltText()|escape}"
								     {/if}>
							</a>
						</div>
					</div>
				{/if}

				<div class="col-lg-7 col-md-7">
					<div class="amcad-issue-info">
						{if $issue->hasDescription()}
							<div class="amcad-issue-description">
								<h3 class="h4">
									{if $issue->getLocalizedTitle()}
										{$issue->getLocalizedTitle()|escape}
									{else}
										{translate key="plugins.themes.AmcadX.issueDescription"}
									{/if}
								</h3>
								{$issue->getLocalizedDescription()|strip_unsafe_html}
							</div>
						{/if}

						{if $issueGalleys}
							<div class="amcad-issue-galleys mt-4">
								<h4 class="h5">{translate key="issue.fullIssue"}</h4>
								<div class="amcad-galley-links">
									{foreach from=$issueGalleys item=galley}
										{include file="frontend/objects/galley_link.tpl" parent=$issue purchaseFee=$currentJournal->getSetting('purchaseIssueFee') purchaseCurrency=$currentJournal->getSetting('currency')}
									{/foreach}
								</div>
							</div>
						{/if}

						<div class="mt-4">
							<a href="{url op="view" page="issue" path=$issue->getBestIssueId()}"
							   class="btn btn-primary amcad-btn">
								{translate key="issue.viewIssue"}
							</a>
						</div>
					</div>
				</div>
			</div>
		</section>
	{/if}

	{* Announcements Section *}
	{if $numAnnouncementsHomepage && $announcements|@count}
		<section class="amcad-announcements mt-5">
			<div class="amcad-section-header text-center mb-4">
				<h2 class="amcad-section-title">{translate key="announcement.announcementsHome"}</h2>
			</div>
			<div class="row">
				{foreach from=$announcements item=announcement}
					<article class="col-md-4 mb-4">
						<div class="amcad-announcement-card card h-100">
							<div class="card-body">
								<h3 class="amcad-announcement-title h5">{$announcement->getLocalizedTitle()|escape}</h3>
								<p class="amcad-announcement-description">
									{$announcement->getLocalizedDescriptionShort()|strip_unsafe_html}
								</p>
								<a href="{url router=$smarty.const.ROUTE_PAGE page="announcement" op="view" path=$announcement->getId()}"
								   class="amcad-read-more">
									{translate key="common.readMore"}
								</a>
							</div>
							<div class="card-footer bg-transparent border-0">
								<small class="text-muted">{$announcement->getDatePosted()|date_format:$dateFormatLong}</small>
							</div>
						</div>
					</article>
				{/foreach}
			</div>
		</section>
	{/if}

	{* Issue Table of Contents *}
	{if $issue}
		<section class="amcad-issue-toc mt-5">
			<div class="row">
				<div class="col-12 col-lg-10 mx-auto">
					{include file="frontend/objects/issue_toc.tpl" sectionHeading="h3"}
				</div>
			</div>

			<div class="text-center mt-4">
				<a class="btn btn-outline-primary amcad-btn-outline"
				   href="{url router=$smarty.const.ROUTE_PAGE page="issue" op="archive"}">
					{translate key="journal.viewAllIssues"}
				</a>
			</div>
		</section>
	{/if}

	{* Additional Homepage Content *}
	{if $additionalHomeContent}
		<section class="amcad-additional-content mt-5">
			<div class="row justify-content-center">
				<div class="col-lg-10">
					{$additionalHomeContent}
				</div>
			</div>
		</section>
	{/if}

</div><!-- .container -->

{include file="frontend/components/footer.tpl"}
