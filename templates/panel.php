<?php
/** @var array $_ */
?>
<main id="legislativo-public-panel" class="legislativo-public-panel" data-session-id="<?php p((string)$_['sessionId']); ?>">
	<header><div><p>Câmara Municipal de Conchal</p><h1 id="panel-session">Plenário eletrônico</h1></div><time id="panel-clock">--:--:--</time></header>
	<section class="panel-overview"><div><span>Situação</span><strong id="panel-status">Carregando</strong></div><div><span>Presença</span><strong id="panel-presence">—</strong></div><div class="panel-timer"><span id="panel-timer-label">Cronômetro</span><strong id="panel-timer">00:00</strong></div></section>
	<div id="panel-error" role="alert" hidden></div>
	<section><h2>Ordem do dia</h2><div id="panel-agenda"></div></section>
	<section><h2>Oradores</h2><div id="panel-speakers" class="panel-speakers"></div></section>
</main>
