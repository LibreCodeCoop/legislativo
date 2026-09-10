<?php

declare(strict_types=1);

namespace OCA\Legislativo\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version6000Date20260804230000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */ $schema=$schemaClosure(); $changed=false;
		$sessions=$schema->getTable('leg_sessions');
		foreach ([
			'timer_label'=>[Types::STRING,['length'=>255,'notnull'=>false]],
			'timer_status'=>[Types::STRING,['length'=>16,'default'=>'idle']],
			'timer_started_at'=>[Types::DATETIME,['notnull'=>false]],
			'timer_duration'=>[Types::INTEGER,['unsigned'=>true,'notnull'=>false]],
		] as $name=>[$type,$definition]) if(!$sessions->hasColumn($name)){ $sessions->addColumn($name,$type,$definition); $changed=true; }

		if(!$schema->hasTable('leg_parliamentarians')){
			$table=$schema->createTable('leg_parliamentarians'); $this->addId($table);
			$table->addColumn('user_uid',Types::STRING,['length'=>64]);
			$table->addColumn('display_name',Types::STRING,['length'=>255]);
			$table->addColumn('party',Types::STRING,['length'=>32,'notnull'=>false]);
			$table->addColumn('role',Types::STRING,['length'=>64,'default'=>'vereador']);
			$table->addColumn('seat_number',Types::SMALLINT,['unsigned'=>true,'notnull'=>false]);
			$table->addColumn('term_start',Types::DATE,[]); $table->addColumn('term_end',Types::DATE,[]);
			$table->addColumn('active',Types::BOOLEAN,['default'=>true]);
			$table->addColumn('created_at',Types::DATETIME,[]); $table->addColumn('updated_at',Types::DATETIME,[]);
			$table->addUniqueIndex(['user_uid'],'leg_parliamentarian_user_uk'); $table->addIndex(['active','display_name'],'leg_parliamentarian_active_idx'); $changed=true;
		}

		if(!$schema->hasTable('leg_speaker_queue')){
			$table=$schema->createTable('leg_speaker_queue'); $this->addId($table);
			$table->addColumn('session_id',Types::BIGINT,['unsigned'=>true]); $table->addColumn('user_uid',Types::STRING,['length'=>64]);
			$table->addColumn('display_name',Types::STRING,['length'=>255]); $table->addColumn('topic',Types::STRING,['length'=>512,'notnull'=>false]);
			$table->addColumn('position',Types::INTEGER,['unsigned'=>true]); $table->addColumn('status',Types::STRING,['length'=>16,'default'=>'waiting']);
			$table->addColumn('allotted_seconds',Types::INTEGER,['unsigned'=>true,'default'=>300]);
			$table->addColumn('requested_at',Types::DATETIME,[]); $table->addColumn('started_at',Types::DATETIME,['notnull'=>false]); $table->addColumn('ended_at',Types::DATETIME,['notnull'=>false]);
			$table->addIndex(['session_id','status','position'],'leg_speaker_queue_idx'); $changed=true;
		}
		return $changed?$schema:null;
	}
	private function addId(object $table):void{$table->addColumn('id',Types::BIGINT,['autoincrement'=>true,'unsigned'=>true]);$table->setPrimaryKey(['id']);}
}
