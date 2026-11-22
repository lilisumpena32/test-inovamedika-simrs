<?php

declare(strict_types=1);

namespace app\components;

use app\models\Menu;
use app\models\User;
use Yii;
use yii\base\Component;
use yii\db\Query;

class Access extends Component
{
    private const TANPA_HAK = [
        'can_view' => false,
        'can_create' => false,
        'can_update' => false,
        'can_delete' => false,
        'can_auth' => false,
    ];

    private ?array $hak = null;

    public function can(string $kodeMenu, string $aksi): bool
    {
        return (bool) ($this->semua()[$kodeMenu]['can_' . $aksi] ?? false);
    }

    public function menu(): array
    {
        $baris = Menu::find()
            ->where(['is_active' => true])
            ->orderBy(['sorter' => SORT_ASC])
            ->all();

        return $this->susun($baris, $this->semua(), null);
    }

    private function semua(): array
    {
        if ($this->hak !== null) {
            return $this->hak;
        }

        $user = Yii::$app->user->identity;
        if (!$user instanceof User) {
            return $this->hak = [];
        }

        $baris = (new Query())
            ->select([
                'm.kode',
                'ra.can_view',
                'ra.can_create',
                'ra.can_update',
                'ra.can_delete',
                'ra.can_auth',
            ])
            ->from(['ua' => '{{%user_access}}'])
            ->innerJoin(['ra' => '{{%role_access}}'], 'ra.menu_id = ua.menu_id')
            ->innerJoin(['m' => '{{%menu}}'], 'm.id = ua.menu_id')
            ->innerJoin(['u' => '{{%user}}'], 'u.id = ua.user_id')
            ->innerJoin(['r' => '{{%role}}'], 'r.id = u.role_id')
            ->where([
                'ua.user_id' => $user->id,
                'ra.role_id' => $user->role_id,
                'u.is_active' => true,
                'u.is_delete' => false,
                'r.is_active' => true,
                'm.is_active' => true,
            ])
            ->all();

        $hasil = [];
        foreach ($baris as $row) {
            $hasil[$row['kode']] = [
                'can_view' => (bool) $row['can_view'],
                'can_create' => (bool) $row['can_create'],
                'can_update' => (bool) $row['can_update'],
                'can_delete' => (bool) $row['can_delete'],
                'can_auth' => (bool) $row['can_auth'],
            ];
        }

        return $this->hak = $hasil;
    }

    private function susun(array $baris, array $hak, ?string $parentId): array
    {
        $hasil = [];

        foreach ($baris as $row) {
            if ($row->parent_id !== $parentId) {
                continue;
            }

            $anak = $this->susun($baris, $hak, (string) $row->id);
            $bolehLihat = $hak[$row->kode]['can_view'] ?? false;

            if (!$bolehLihat && $anak === []) {
                continue;
            }

            $hasil[] = [
                'kode' => $row->kode,
                'label' => $row->label,
                'path' => $row->path,
                'icon' => $row->icon,
                'sorter' => (int) $row->sorter,
                'can' => $hak[$row->kode] ?? self::TANPA_HAK,
                'children' => $anak,
            ];
        }

        return $hasil;
    }
}
