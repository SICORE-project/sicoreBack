<?php

namespace Tests\Unit;

use App\Support\TeacherRib;
use PHPUnit\Framework\TestCase;

class TeacherRibTest extends TestCase
{
    public function test_excel_examples_and_invalid_lengths(): void
    {
        foreach ([
            ['SN012', '01301', '035159896701', '97'],
            ['SN010', '01520', '015618200086', '22'],
            ['SN012', '08276', '027635000077', '47'],
            ['SN010', '11415', '020073700011', '90'],
            ['SN010', '01520', '020646600041', '82'],
            ['SN012', '05290', '035196158401', '55'],
        ] as [$bank, $branch, $account, $key]) {
            self::assertSame($key, TeacherRib::key($bank, $branch, $account));
        }
        self::assertSame('07', TeacherRib::key('SN012', '01301', '000000000017'));
        self::assertNull(TeacherRib::key('SN012', '013010', '035159896701'));
        self::assertNull(TeacherRib::key('SN012', '01301', '0351598967010'));
        self::assertNull(TeacherRib::key('SN012', '01301', '03515989670A'));
    }

    public function test_server_recalculates_key_and_complete_rib(): void
    {
        $account = TeacherRib::prepare([
            'code_banque' => 'sn012', 'code_guichet' => '01301',
            'numero_compte' => '035159896701', 'cle_rib' => '00', 'rib' => 'incorrect',
        ]);
        self::assertSame('97', $account['cle_rib']);
        self::assertSame('SN0120130103515989670197', $account['rib']);
        self::assertSame(24, strlen($account['rib']));
    }
}
