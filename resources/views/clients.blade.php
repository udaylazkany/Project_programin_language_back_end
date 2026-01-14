<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة المستخدمين</title>

    <style>
        /* ============================
        Global Page Styling
        ============================ */
        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            padding: 20px;
            background-color: #fafafa;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            color: #333;
            min-height: 100vh;
        }

        /* ============================
        Table Styling
        ============================ */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e5e5e5;
            text-align: center;
            font-size: 15px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 16px;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        /* ============================
        Status Buttons (ON / OFF)
        ============================ */
        .on-btn {
            background-color: #28a745;
            color: white;
            padding: 7px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        .on-btn:hover {
            background-color: #1e7e34;
        }

        .off-btn {
            background-color: #dc3545;
            color: white;
            padding: 7px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        .off-btn:hover {
            background-color: #b52a37;
        }

        /* ============================
        Role Buttons (Owner / Tenant)
        ============================ */
        .role-btn {
            padding: 7px 18px;
            border: none;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            color: white;
            font-size: 14px;
        }

        .role-btn.owner {
            background-color: #007bff;
        }

        .role-btn.owner:hover {
            background-color: #0056b3;
        }

        .role-btn.tenant {
            background-color: #28a745;
        }

        .role-btn.tenant:hover {
            background-color: #1e7e34;
        }

        /* ============================
        Logout Button
        ============================ */
        .logout-btn {
            background-color: #ff4757;
            color: white;
            padding: 12px 22px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background-color: #e84118;
        }

        /* ============================
        Header Bar
        ============================ */
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        /* ============================
        Responsive Table
        ============================ */
        @media (max-width: 768px) {
            table {
                font-size: 13px;
            }

            th, td {
                padding: 8px;
            }

            .role-btn,
            .on-btn,
            .off-btn {
                padding: 5px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

<div class="header-bar">
    <h2>قائمة المستخدمين</h2>

    <form method="POST" action="/logout">
        @csrf
        <button class="logout-btn">تسجيل الخروج</button>
    </form>
</div>

<table id="clientsTable">
    <thead>
        <tr>
            <th>الاسم الأول</th>
            <th>الكنية</th>
            <th>رقم الهاتف</th>
            <th>الدور</th>
            <th>الحالة</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($clients as $client)
            <tr>
                <td>{{ $client->firstName }}</td>
                <td>{{ $client->lastName }}</td>
                <td>{{ $client->phoneNumber }}</td>

                <td>
                    <form method="POST" action="/clients/toggle-role/{{ $client->id }}">
                        @csrf
                        <button class="role-btn {{ $client->role == 'owner' ? 'owner' : 'tenant' }}">
                            {{ $client->role == 'owner' ? 'Owner' : 'Tenant' }}
                        </button>
                    </form>
                </td>

                <td>
                    <form method="POST" action="/clients/toggle-status/{{ $client->id }}">
    @csrf
    <input type="hidden" name="is_approved" value="{{ $client->is_approved ? 0 : 1 }}">
    <button class="{{ $client->is_approved ? 'off-btn' : 'on-btn' }}">
        {{ $client->is_approved ? 'إلغاء الموافقة' : 'تفعيل الموافقة' }}
    </button>
</form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
