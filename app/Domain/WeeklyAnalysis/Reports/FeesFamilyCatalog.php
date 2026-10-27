<?php

namespace App\Domain\WeeklyAnalysis\Reports;

class FeesFamilyCatalog
{
    public const RHP = 'RHP';

    public const CM = 'CM';

    public const SM_WOOD = 'Sm Wood';

    public const HANDMETER = 'Handmeter';

    public const LG_WOOD = 'Lg Wood';

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        return [
            self::RHP,
            self::CM,
            self::SM_WOOD,
            self::HANDMETER,
            self::LG_WOOD,
        ];
    }

    public static function forMeterLabel(?string $meterLabel): string
    {
        $label = trim((string) $meterLabel);

        if ($label === '') {
            return self::RHP;
        }

        if (str_starts_with($label, 'RHP')
            || in_array($label, ['BT Smart Loggers', 'Floor Sentry', 'True Remote Monitoring', '5.0 RHP Prod'], true)
        ) {
            return self::RHP;
        }

        if (in_array($label, ['C555 Concrete Kits', 'C575'], true)) {
            return self::CM;
        }

        if (in_array($label, ['Orion 910', 'Orion 920', 'Orion 930', 'Orion 940', 'Orion 950'], true)) {
            return self::SM_WOOD;
        }

        if (in_array($label, [
            'Handmeter Misc',
            'BI 2200',
            'MMI 1100',
            'MMC205',
            'L607',
            'MMC210',
            'MMC220',
        ], true)) {
            return self::HANDMETER;
        }

        if (in_array($label, [
            'L5200/L5300',
            'L601-3',
            'L610/L620',
            'L612/L622',
            'L722-L',
            'L722-S',
            'L622/L722 Kits',
            'L5300/L722',
        ], true)) {
            return self::LG_WOOD;
        }

        return self::RHP;
    }
}
