namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceExport implements FromCollection, WithHeadings
{
    protected $attendances;

    public function __construct($attendances)
    {
        $this->attendances = $attendances;
    }

    public function collection()
    {
        return collect($this->attendances)->map(function ($a) {
            return [
                'Nama' => $a['user']['name'] ?? '-',
                'Tanggal' => $a['date'],
                'Clock In' => $a['clock_in'],
                'Clock Out' => $a['clock_out'],
            ];
        });
    }

    public function headings(): array
    {
        return ['Nama', 'Tanggal', 'Clock In', 'Clock Out'];
    }
}
