{**
 * Custom Search Page - AMCAD
 * Página de búsqueda totalmente personalizada
 *}
{include file="frontend/components/header.tpl" pageTitle="Búsqueda"}

<div class="custom-search-page">
    <div class="container">

        {* Hero de Búsqueda Personalizado *}
        <div class="search-hero">
            <h1 class="search-title">Buscar en AMCAD</h1>
            <p class="search-subtitle">Encuentra artículos, autores y más</p>

            {* Formulario de búsqueda personalizado *}
            <form class="custom-search-form" method="get" action="{url page="search" op="search"}">
                <div class="search-input-wrapper">
                    <input type="text"
                           name="query"
                           class="custom-search-input"
                           placeholder="¿Qué estás buscando?"
                           value="{$searchQuery|escape}">
                    <button type="submit" class="custom-search-btn">
                        <i class="fa fa-search"></i> Buscar
                    </button>
                </div>

                {* Filtros adicionales *}
                <div class="search-filters">
                    <label>
                        <input type="checkbox" name="authors" value="1"> Buscar en autores
                    </label>
                    <label>
                        <input type="checkbox" name="title" value="1" checked> Buscar en títulos
                    </label>
                    <label>
                        <input type="checkbox" name="abstract" value="1"> Buscar en resúmenes
                    </label>
                </div>
            </form>
        </div>

        {* Resultados de búsqueda *}
        {if $results}
            <div class="search-results">
                <h2>Resultados de búsqueda ({$results|@count})</h2>

                <div class="results-grid">
                    {foreach from=$results item=result}
                        <div class="result-card">
                            <h3 class="result-title">
                                <a href="{url page="article" op="view" path=$result->getId()}">
                                    {$result->getLocalizedTitle()|escape}
                                </a>
                            </h3>
                            <p class="result-authors">
                                {$result->getAuthorString()|escape}
                            </p>
                            <p class="result-abstract">
                                {$result->getLocalizedAbstract()|strip_tags|truncate:200}
                            </p>
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}

    </div>
</div>

{include file="frontend/components/footer.tpl"}
