<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <title>
        Admission Form - {{ $student->form_id ?? '' }}
    </title>

    <style>

        @page {
            size: A4 portrait;
            margin: 22px 38px 22px 38px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #000;
            font-size: 9px;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 100%;
        }

        /* =========================================================
           SCHOOL HEADER
        ========================================================= */

        .school-header {
            text-align: center;
            margin-bottom: 8px;
        }

        .school-logo {
            width: 55px;
            height: 55px;
            object-fit: contain;
            margin-bottom: 3px;
        }

        .school-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .school-subtitle {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .academic-year {
            font-size: 9px;
            font-weight: bold;
        }

        /* =========================================================
           FORM NUMBER
        ========================================================= */

        .form-number {
            text-align: right;
            font-size: 8px;
            font-weight: bold;
            margin-top: 8px;
            margin-bottom: 9px;
        }

        /* =========================================================
           SECTION HEADINGS
        ========================================================= */

        .section-title {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin-top: 7px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .section-subtitle {
            font-size: 6px;
            display: block;
            margin-top: 2px;
        }

        /* =========================================================
           COMMON TABLE
        ========================================================= */

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* =========================================================
           STUDENT PROFILE
        ========================================================= */

        .student-table {
            margin-bottom: 8px;
        }

        .student-table td {
            padding: 4px 3px;
            height: 22px;
            vertical-align: bottom;
            font-size: 8px;
        }

        .student-label {
            width: 15%;
            white-space: nowrap;
        }

        .student-value {
            width: 35%;
            border-bottom: 1px dotted #555;
            min-height: 16px;
        }

        /* =========================================================
           PARENT ADDRESS
        ========================================================= */

        .address-table {
            margin-top: 2px;
            margin-bottom: 8px;
        }

        .address-table td {
            padding: 4px 6px;
            vertical-align: top;
        }

        .address-heading {
            width: 50%;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            padding-bottom: 5px !important;
        }

        .address-value {
            height: 40px;
            font-size: 7px;
            line-height: 1.4;
            vertical-align: top !important;
            text-align: center;
            padding-top: 5px !important;
        }

        /* =========================================================
           PARENT PROFILE
        ========================================================= */

        .parent-table {
            margin-top: 2px;
        }

        .parent-table th {
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            padding: 4px;
        }

        .parent-table td {
            padding: 4px 3px;
            height: 21px;
            font-size: 7px;
            vertical-align: bottom;
        }

        .parent-label {
            width: 24%;
        }

        .parent-value {
            width: 38%;
            border-bottom: 1px dotted #555;
        }

        /* =========================================================
           PAGE BREAK
        ========================================================= */

        .page-break {
            page-break-before: always;
        }

        /* =========================================================
           PAGE 2
        ========================================================= */

        .page-two {
            padding-top: 2px;
        }

        /* =========================================================
           INDEMNITY BOND
        ========================================================= */

        .bond-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin-top: 2px;
            margin-bottom: 9px;
        }

        .bond-text {
            font-size: 8px;
            line-height: 1.5;
            text-align: justify;
            margin-bottom: 16px;
        }

        /* =========================================================
           DECLARATION
        ========================================================= */

        .declaration-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 9px;
        }

        .declaration {
            font-size: 8px;
            line-height: 1.5;
            text-align: justify;
        }

        .declaration p {
            margin-top: 0;
            margin-bottom: 9px;
        }

        /* =========================================================
           SIGNATURES
        ========================================================= */

        .signature-table {
            margin-top: 20px;
            margin-bottom: 7px;
        }

        .signature-table td {
            width: 50%;
            border: none;
            text-align: center;
            padding: 5px 30px;
        }

        .signature-line {
            height: 22px;
            border-bottom: 1px dotted #555;
            margin-bottom: 4px;
        }

        .signature-label {
            font-size: 8px;
            font-weight: bold;
        }

        .date {
            font-size: 8px;
            margin-top: 4px;
        }

        /* =========================================================
           DOCUMENTS
        ========================================================= */

        .documents-title {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin-top: 18px;
            margin-bottom: 7px;
        }

        .documents-table {
            width: 100%;
            border-collapse: collapse;
        }

        .documents-table td {
            width: 33.33%;
            padding: 5px 3px;
            height: 25px;
            vertical-align: middle;
            font-size: 7px;
        }

        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            text-align: center;
            line-height: 8px;
            font-size: 8px;
            margin-right: 4px;
            vertical-align: middle;
        }

        /* =========================================================
           OFFICE USE
        ========================================================= */

        .office-title {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin-top: 17px;
            margin-bottom: 7px;
        }

        .office-table {
            width: 100%;
            border-collapse: collapse;
        }

        .office-table td {
            padding: 6px 5px;
            height: 25px;
            font-size: 8px;
        }

        .office-label {
            font-weight: bold;
        }

        .office-line {
            display: inline-block;
            width: 120px;
            height: 14px;
            border-bottom: 1px dotted #555;
            vertical-align: bottom;
        }

        /* =========================================================
           PRINCIPAL
        ========================================================= */

        .principal {
            text-align: right;
            margin-top: 28px;
            font-size: 8px;
            font-weight: bold;
        }

        .principal-line {
            display: inline-block;
            width: 170px;
            height: 20px;
            border-bottom: 1px dotted #555;
            margin-bottom: 3px;
        }

        /* =========================================================
           HELPERS
        ========================================================= */

        .text-center {
            text-align: center;
        }

        .small {
            font-size: 6px;
        }

    </style>

</head>


<body>

@php

    /*
    |--------------------------------------------------------------------------
    | Student Name
    |--------------------------------------------------------------------------
    */

    $studentName = trim(
        ($student->first_name ?? '') . ' ' .
        ($student->mid_name ?? '') . ' ' .
        ($student->last_name ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | Gender
    |--------------------------------------------------------------------------
    */

    $gender = match (
        strtoupper((string) ($student->gender ?? ''))
    ) {

        'M' => 'MALE',

        'F' => 'FEMALE',

        'O' => 'OTHER',

        default => $student->gender ?? '',

    };


    /*
    |--------------------------------------------------------------------------
    | Present Address
    |--------------------------------------------------------------------------
    */

    $presentAddress = collect([

        $student->locality ?? null,

        $student->city ?? null,

        $student->state ?? null,

        $student->pincode ?? null,

    ])
        ->filter(
            fn ($value) =>
                $value !== null &&
                $value !== ''
        )
        ->implode(', ');


    /*
    |--------------------------------------------------------------------------
    | Permanent Address
    |--------------------------------------------------------------------------
    */

    $permanentAddress =
        $student->perm_address ?? '';


    /*
    |--------------------------------------------------------------------------
    | Class Name
    |--------------------------------------------------------------------------
    */

    $displayClass =
        $className
        ?? $student->class_name
        ?? $student->class_id
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    $displayStatus =
        $student->admission_form_status
        ?? $student->status
        ?? '';


    $statusMap = [

        'S' => 'Applied',

        'A' => 'Applied',

        'APPLIED' => 'Applied',

        'D' => 'Draft',

        'DRAFT' => 'Draft',

    ];


    $displayStatus =
        $statusMap[
            strtoupper((string) $displayStatus)
        ]
        ?? $displayStatus;


    /*
    |--------------------------------------------------------------------------
    | Uploaded Documents
    |--------------------------------------------------------------------------
    */

    $uploadedDocumentTypes = collect(
        $documents ?? []
    )
        ->pluck('doc_type')
        ->map(
            fn ($type) =>
                strtoupper((string) $type)
        )
        ->values()
        ->toArray();


    /*
    |--------------------------------------------------------------------------
    | Document Helper
    |--------------------------------------------------------------------------
    */

    $hasDocument = function (
        array $keywords
    ) use (
        $uploadedDocumentTypes
    ) {

        foreach ($uploadedDocumentTypes as $type) {

            foreach ($keywords as $keyword) {

                if (
                    str_contains(
                        $type,
                        strtoupper($keyword)
                    )
                ) {

                    return true;
                }
            }
        }

        return false;
    };


    /*
    |--------------------------------------------------------------------------
    | Academic Year
    |--------------------------------------------------------------------------
    */

    $academicYear =
        $student->academic_yr ?? '';


    /*
    |--------------------------------------------------------------------------
    | Form Number
    |--------------------------------------------------------------------------
    */

    $formNumber =
        $student->form_id ?? '';

@endphp


<!-- ============================================================= -->
<!-- PAGE 1 -->
<!-- ============================================================= -->

<div class="page">


    <!-- ========================================================= -->
    <!-- SCHOOL HEADER -->
    <!-- ========================================================= -->

    <div class="school-header">

        @if(
            file_exists(
                public_path('images/school/logo.jpg')
            )
        )

            <img
                src="{{ public_path('images/school/logo.jpg') }}"
                class="school-logo"
            >

        @elseif(
            file_exists(
                public_path('images/school/logo.png')
            )
        )

            <img
                src="{{ public_path('images/school/logo.png') }}"
                class="school-logo"
            >

        @endif


        <div class="school-name">
            DIVINE WORD NURSERY
        </div>


        <div class="school-subtitle">
            (St. Arnold's Pre Primary, Pune)
        </div>


        <div class="academic-year">

            {{ $displayClass ?: 'Nursery' }}
            Academic Year
            ({{ $academicYear }})

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- FORM NUMBER -->
    <!-- ========================================================= -->

    <div class="form-number">

        FORM No.
        {{ $formNumber }}

        @if(!empty($student->adm_form_pk))

            ({{ $student->adm_form_pk }})

        @endif

    </div>


    <!-- ========================================================= -->
    <!-- STUDENT PROFILE -->
    <!-- ========================================================= -->

    <div class="section-title">

        STUDENT'S PROFILE

        <span class="section-subtitle">
            (NAME AS PER BIRTH CERTIFICATE ONLY)
        </span>

    </div>


    <table class="student-table">


        <!-- ROW 1 -->

        <tr>

            <td class="student-label">
                First Name
            </td>

            <td class="student-value">
                {{ $student->first_name ?? '' }}
            </td>

            <td class="student-label">
                Middle Name
            </td>

            <td class="student-value">
                {{ $student->mid_name ?? '' }}
            </td>

        </tr>


        <!-- ROW 2 -->

        <tr>

            <td class="student-label">
                Surname
            </td>

            <td class="student-value">
                {{ $student->last_name ?? '' }}
            </td>

            <td class="student-label">
                Gender
            </td>

            <td class="student-value">
                {{ $gender }}
            </td>

        </tr>


        <!-- ROW 3 -->

        <tr>

            <td class="student-label">
                Date of Birth
            </td>

            <td class="student-value">

                @if(!empty($student->dob))

                    {{ \Carbon\Carbon::parse(
                        $student->dob
                    )->format('d-m-Y') }}

                @endif

            </td>

            <td class="student-label">
                Aadhar UID
            </td>

            <td class="student-value">
                {{ $student->stud_aadhar ?? '' }}
            </td>

        </tr>


        <!-- ROW 4 -->

        <tr>

            <td class="student-label">
                Blood Group
            </td>

            <td class="student-value">
                {{ $student->blood_group ?? '' }}
            </td>

            <td class="student-label">
                Religion
            </td>

            <td class="student-value">
                {{ $student->religion ?? '' }}
            </td>

        </tr>


        <!-- ROW 5 -->

        <tr>

            <td class="student-label">
                Mother Tongue
            </td>

            <td class="student-value">
                {{ $student->mother_tongue ?? '' }}
            </td>

            <td class="student-label">
                Caste
            </td>

            <td class="student-value">
                {{ $student->caste ?? '' }}
            </td>

        </tr>


        <!-- ROW 6 -->

        <tr>

            <td class="student-label">
                Sub-Caste
            </td>

            <td class="student-value">
                {{ $student->subcaste ?? '' }}
            </td>

            <td class="student-label">
                Category
            </td>

            <td class="student-value">
                {{ $student->category ?? '' }}
            </td>

        </tr>


        <!-- ROW 7 -->

        <tr>

            <td class="student-label">
                Class
            </td>

            <td class="student-value">
                {{ $displayClass }}
            </td>

            <td class="student-label">
                Status
            </td>

            <td class="student-value">
                {{ $displayStatus }}
            </td>

        </tr>


    </table>


    <!-- ========================================================= -->
    <!-- PARENT ADDRESS -->
    <!-- ========================================================= -->

    <div class="section-title">
        PARENT'S ADDRESS
    </div>


    <table class="address-table">


        <tr>

            <td class="address-heading">
                PRESENT ADDRESS
            </td>

            <td class="address-heading">
                PERMANENT ADDRESS
            </td>

        </tr>


        <tr>

            <td class="address-value">
                {{ $presentAddress }}
            </td>

            <td class="address-value">
                {{ $permanentAddress }}
            </td>

        </tr>


    </table>


    <!-- ========================================================= -->
    <!-- PARENT PROFILE -->
    <!-- ========================================================= -->

    <div class="section-title">
        PARENT'S PROFILE
    </div>


    <table class="parent-table">


        <!-- HEADER -->

        <tr>

            <th class="parent-label"></th>

            <th>
                FATHER
            </th>

            <th>
                MOTHER
            </th>

        </tr>


        <!-- NAME -->

        <tr>

            <td class="parent-label">
                NAME
            </td>

            <td class="parent-value">
                {{ $student->father_name ?? '' }}
            </td>

            <td class="parent-value">
                {{ $student->mother_name ?? '' }}
            </td>

        </tr>


        <!-- QUALIFICATION -->

        <tr>

            <td class="parent-label">
                QUALIFICATION
            </td>

            <td class="parent-value">
                {{ $student->f_qualification ?? '' }}
            </td>

            <td class="parent-value">
                {{ $student->m_qualification ?? '' }}
            </td>

        </tr>


        <!-- AADHAR -->

        <tr>

            <td class="parent-label">
                AADHAR UID
            </td>

            <td class="parent-value">
                {{ $student->f_aadhar_no ?? '' }}
            </td>

            <td class="parent-value">
                {{ $student->m_aadhar_no ?? '' }}
            </td>

        </tr>


        <!-- OCCUPATION -->

        <tr>

            <td class="parent-label">
                OCCUPATION
            </td>

            <td class="parent-value">
                {{ $student->father_occupation ?? '' }}
            </td>

            <td class="parent-value">
                {{ $student->mother_occupation ?? '' }}
            </td>

        </tr>


        <!-- MOBILE -->

        <tr>

            <td class="parent-label">
                MOBILE NUMBER
            </td>

            <td class="parent-value">
                {{ $student->f_mobile ?? '' }}
            </td>

            <td class="parent-value">
                {{ $student->m_mobile ?? '' }}
            </td>

        </tr>


        <!-- EMAIL -->

        <tr>

            <td class="parent-label">
                EMAIL ID
            </td>

            <td class="parent-value">
                {{ $student->f_email ?? '' }}
            </td>

            <td class="parent-value">
                {{ $student->m_emailid ?? '' }}
            </td>

        </tr>


    </table>


</div>


<!-- ============================================================= -->
<!-- PAGE 2 -->
<!-- ============================================================= -->

<div class="page-break"></div>


<div class="page page-two">


    <!-- ========================================================= -->
    <!-- INDEMNITY BOND -->
    <!-- ========================================================= -->

    <div class="bond-title">
        INDEMNITY BOND
    </div>


    <div class="bond-text">

        We shall not hold the school or authorities responsible
        for injuries or loss of life suffered in course of
        everyday activities.

    </div>


    <!-- ========================================================= -->
    <!-- DECLARATION -->
    <!-- ========================================================= -->

    <div class="declaration-title">
        DECLARATION
    </div>


    <div class="declaration">


        <p>

            I/we hereby certify that the above information provided
            by me/us is correct and I/we understand that if the
            information is found to be incorrect or false, the ward
            shall be automatically debarred from admission process/
            selection without any correspondence in this regard.

        </p>


        <p>

            I/we understand that the registration/short listing does
            not guarantee admission to my/our ward. I/We accept the
            admission process undertaken by the school and the
            decision taken by the school authorities.

        </p>


        <p>

            I/We hereby promise to abide by the decision of the school
            management, with regard to revision of fee. It may be to
            the extent of 10 to 15% every year as determined by the
            management. I/We understand this will be done in the
            interest of development of my/our child/children.

        </p>


    </div>


    <!-- ========================================================= -->
    <!-- SIGNATURES -->
    <!-- ========================================================= -->

    <table class="signature-table">


        <tr>

            <td>

                <div class="signature-line"></div>

                <div class="signature-label">
                    FATHER'S SIGN.
                </div>

            </td>


            <td>

                <div class="signature-line"></div>

                <div class="signature-label">
                    MOTHER'S SIGN.
                </div>

            </td>

        </tr>


    </table>


    <!-- ========================================================= -->
    <!-- DATE -->
    <!-- ========================================================= -->

    <div class="date">

        DATE

        @if(!empty($student->application_date))

            {{ \Carbon\Carbon::parse(
                $student->application_date
            )->format('d-m-Y') }}

        @else

            {{ now()->format('d-m-Y') }}

        @endif

    </div>


    <!-- ========================================================= -->
    <!-- DOCUMENT CHECKLIST -->
    <!-- ========================================================= -->

    <div class="documents-title">
        Uploaded Documents (Kindly tick)
    </div>


    <table class="documents-table">


        <!-- ROW 1 -->

        <tr>

            <td>

                <span class="checkbox">

                    @if(
                        $hasDocument([
                            'BIRTH',
                            'BIRTH CERTIFICATE'
                        ])
                    )
                        ✓
                    @endif

                </span>

                Birth Certificate

            </td>


            <td>

                <span class="checkbox">

                    @if(
                        $hasDocument([
                            'BAPTISM'
                        ])
                    )
                        ✓
                    @endif

                </span>

                Baptism Certificate
                (Christians only)

            </td>


            <td>

                <span class="checkbox">

                    @if(
                        $hasDocument([
                            'CASTE'
                        ])
                    )
                        ✓
                    @endif

                </span>

                Caste Certificate
                (if applicable)

            </td>

        </tr>


        <!-- ROW 2 -->

        <tr>

            <td>

                <span class="checkbox">

                    @if(
                        $hasDocument([
                            'STUDENT AADHAR',
                            'STUDENT',
                            'AADHAR'
                        ])
                    )
                        ✓
                    @endif

                </span>

                Student's Aadhar Card

            </td>


            <td>

                <span class="checkbox">

                    @if(
                        $hasDocument([
                            'FATHER AADHAR',
                            'FATHER'
                        ])
                    )
                        ✓
                    @endif

                </span>

                Father's Aadhar Card

            </td>


            <td>

                <span class="checkbox">

                    @if(
                        $hasDocument([
                            'MOTHER AADHAR',
                            'MOTHER'
                        ])
                    )
                        ✓
                    @endif

                </span>

                Mother's Aadhar Card

            </td>

        </tr>


        <!-- ROW 3 -->

        <tr>

            <td>

                <span class="checkbox">

                    @if(
                        $hasDocument([
                            'FATHER QUALIFICATION',
                            'FATHER EDUCATION'
                        ])
                    )
                        ✓
                    @endif

                </span>

                Father's Highest
                Qualification Certificate

            </td>


            <td>

                <span class="checkbox">

                    @if(
                        $hasDocument([
                            'MOTHER QUALIFICATION',
                            'MOTHER EDUCATION'
                        ])
                    )
                        ✓
                    @endif

                </span>

                Mother's Highest
                Qualification Certificate

            </td>


            <td>

                <span class="checkbox">

                    @if(
                        $hasDocument([
                            'PHOTO',
                            'FAMILY PHOTO'
                        ])
                    )
                        ✓
                    @endif

                </span>

                Student photo &
                Family photo

            </td>

        </tr>


    </table>


    <!-- ========================================================= -->
    <!-- OFFICE USE -->
    <!-- ========================================================= -->

    <div class="office-title">
        For Office Use
    </div>


    <table class="office-table">


        <tr>

            <td>

                <span class="checkbox"></span>

                ADMITTED

            </td>


            <td>

                <span class="checkbox"></span>

                NOT ADMITTED

            </td>

        </tr>


        <tr>

            <td>

                <span class="office-label">
                    CLASS
                </span>

                <span class="office-line"></span>

            </td>


            <td>

                <span class="office-label">
                    W.E.F.
                </span>

                <span class="office-line"></span>

            </td>

        </tr>


    </table>


    <!-- ========================================================= -->
    <!-- PRINCIPAL SIGNATURE -->
    <!-- ========================================================= -->

    <div class="principal">

        <div class="principal-line"></div>

        <br>

        SIGNATURE OF THE PRINCIPAL

    </div>


</div>


</body>

</html>