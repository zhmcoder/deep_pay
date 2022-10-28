<?php

declare(strict_types=1);

namespace Yansongda\Pay\Plugin\Alipay\Shortcut;

use Yansongda\Pay\Contract\ShortcutInterface;
use Yansongda\Pay\Plugin\Alipay\Tools\SystemOauthTokenPlugin;

class ApiShortcut implements ShortcutInterface
{
    // 支付宝账户信息获取
    public function getPlugins(array $params): array
    {
        return [
            SystemOauthTokenPlugin::class,
        ];
    }
}
