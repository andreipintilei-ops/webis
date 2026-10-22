{{--
    Software la comandă, 01 "Acum": how the work runs now, as a slightly
    untidy pile on the grey — a few spreadsheet cells (one in red), an email
    thread, a file's chip, a paper form with its signature line. Decorative;
    sized in em off the box's width (css/site/software.css, .sw-now).
--}}
<div class="sw-now" aria-hidden="true">
    <div class="sw-now__sheet">
        <table>
            <tr><th></th><th>A</th><th>B</th><th>C</th></tr>
            <tr><th>1</th><td>Cerere</td><td>Suma</td><td>Status</td></tr>
            <tr><th>2</th><td>#2378</td><td>1.240</td><td>?</td></tr>
            <tr><th>3</th><td>#2379</td><td class="is-error">#REF!</td><td>trimis</td></tr>
            <tr><th>4</th><td>#2380</td><td>860</td><td></td></tr>
        </table>
    </div>

    <div class="sw-now__mail">
        <p class="sw-now__mail-head"><x-software.icon name="mail" /><b>RE: RE: FW: situație</b></p>
        <p class="sw-now__mail-line">Către: contabilitate, secretariat</p>
        <p class="sw-now__mail-line">Am atașat varianta corectată…</p>
        <span class="sw-now__mail-count">7</span>
    </div>

    <div class="sw-now__file"><x-software.icon name="sheet" />FINAL_v3_corectat.xlsx</div>

    <div class="sw-now__paper">
        <p class="sw-now__paper-title">Cerere</p>
        <i></i><i></i><i></i>
        <p class="sw-now__sign">Semnătura</p>
    </div>
</div>
