{{--
    Grid or list, as shops let you choose, for the page's work list
    (components/site/work-list); the grid by default. The script
    (js/public/work-list.ts) shows the switch, keeps the choice (this
    browser) and shows the one view; without it, the grid.
    css/site/work-list.css.
--}}
<div class="work-list__bar" data-work-views hidden>
    <span class="work-list__bar-label" id="work-view-label">Afișare</span>
    <div class="work-list__switch" role="group" aria-labelledby="work-view-label">
        <button type="button" class="work-list__view" data-work-view="grid" aria-pressed="true">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/></svg>
            Grilă
        </button>
        <button type="button" class="work-list__view" data-work-view="list" aria-pressed="false">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            Listă
        </button>
    </div>
</div>
