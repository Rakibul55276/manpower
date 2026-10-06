<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $employee->name }} - Curriculum Vitae</title>
<style>
@page{margin:0 0 42px}*{box-sizing:border-box}body{margin:0;font-family:'DejaVu Sans',sans-serif;color:#22383c;font-size:9.5px;line-height:1.55;background:#fff}.hero{background:#102f35;color:#fff;padding:30px 38px 27px;border-bottom:6px solid #18a494}.hero-table,.layout,.contact-grid{width:100%;border-collapse:collapse}.hero-table td{border:0;vertical-align:middle}.hero-copy{padding-right:24px}.kicker{font-size:7.5px;letter-spacing:2.2px;text-transform:uppercase;color:#90e1ca;font-weight:bold;margin-bottom:8px}.hero h1{font-size:27px;line-height:1.08;margin:0 0 7px;letter-spacing:-.6px;font-weight:700}.role{font-size:12px;color:#d5ebe6;margin-bottom:10px}.hero-meta{font-size:8.5px;color:#accbc6}.photo{width:88px;height:104px;object-fit:cover;border:4px solid #fff;background:#e8f1ee}.layout{margin:0}.layout>tbody>tr>td{vertical-align:top}.sidebar{width:31%;background:#eef5f2;padding:25px 21px 30px 38px}.main{width:69%;padding:25px 38px 30px 27px}.section{margin-bottom:21px}.section-title{font-size:9px;letter-spacing:1.5px;text-transform:uppercase;font-weight:bold;color:#087f74;border-bottom:1px solid #c9dcd6;padding-bottom:6px;margin:0 0 10px}.main .section-title{font-size:10px;color:#173b40;border-bottom:2px solid #18a494}.item{margin-bottom:10px;page-break-inside:avoid}.item-label{font-size:7px;letter-spacing:.8px;text-transform:uppercase;color:#718786;margin-bottom:2px}.item-value{font-size:9.5px;color:#17383d;overflow-wrap:anywhere}.summary{font-size:10px;line-height:1.7;margin:0;white-space:pre-wrap}.skills{margin:0;padding-left:15px}.skills li{margin:0 0 5px;padding-left:2px}.education{white-space:pre-wrap;margin:0;line-height:1.65}.experience-item{position:relative;margin:0 0 18px;padding:0 0 2px 15px;border-left:2px solid #bdd9d1;page-break-inside:avoid}.experience-item:before{content:'';position:absolute;width:7px;height:7px;border-radius:50%;background:#18a494;left:-4.5px;top:4px}.experience-head{width:100%;border-collapse:collapse;margin-bottom:2px}.experience-head td{border:0;padding:0;vertical-align:top}.experience-role{font-size:11px;font-weight:bold;color:#12383e}.experience-date{text-align:right;color:#607977;font-size:8px;white-space:nowrap}.experience-company{font-size:9px;color:#087f74;font-weight:bold;margin:2px 0 7px}.experience-location{color:#718786;font-weight:normal}.responsibilities{white-space:pre-wrap;line-height:1.65;color:#40585b}.empty{color:#778b8c;font-style:italic}.confidential{font-size:7px;color:#79908e;margin-top:18px;padding-top:8px;border-top:1px solid #d8e5e1}.document-note{padding:10px 12px;background:#eef5f2;border-left:3px solid #18a494;color:#40585b;page-break-inside:avoid}
.layout{display:block;width:100%}.hero{padding-top:24px;padding-bottom:21px}.sidebar{position:static;width:auto;padding:13px 38px 9px;background:#eef5f2}.sidebar .section{display:block;width:auto;margin:0 0 8px}.sidebar .section-title{margin-bottom:6px;padding-bottom:4px}.sidebar .item{margin-bottom:5px}.sidebar .skills li{margin-bottom:2px}.sidebar .confidential{display:none}.main{width:auto;margin:0;padding:18px 38px 24px}.main .section{margin-bottom:15px}.experience-item{page-break-inside:auto;margin-bottom:12px;border-left:0;padding-left:15px}.experience-item:before{display:none}.experience-date{text-align:left;margin:2px 0 4px}.responsibilities{font-size:9px;line-height:1.45}
</style>
</head>
<body>
@include('pdf.company-brand')
<div class="hero">
    <table class="hero-table"><tr>
        <td class="hero-copy">
            <div class="kicker">{{ $documentBrand ? $documentBrand->company_name.' · ' : '' }}Curriculum Vitae</div>
            <h1>{{ $employee->name }}</h1>
            <div class="role">{{ $employee->designation->name }}</div>
            <div class="hero-meta">{{ $employee->company->name }}@if($employee->nationality) &nbsp;|&nbsp; {{ $employee->nationality }}@endif</div>
        </td>
        <td style="width:102px;text-align:right">@if($photo)<img class="photo" src="{{ $photo }}" alt="Employee photo">@endif</td>
    </tr></table>
</div>
<div class="layout">
<div class="sidebar">
    <div class="section"><div class="section-title">Contact</div>
        <div class="item"><div class="item-label">Phone</div><div class="item-value">{{ $employee->phone }}</div></div>
        @if($employee->personal_email)<div class="item"><div class="item-label">Email</div><div class="item-value">{{ $employee->personal_email }}</div></div>@endif
    </div>
    <div class="section"><div class="section-title">Professional details</div>
        <div class="item"><div class="item-label">Current designation</div><div class="item-value">{{ $employee->designation->name }}</div></div>
        <div class="item"><div class="item-label">Current company</div><div class="item-value">{{ $employee->company->name }}</div></div>
        @if($employee->nationality)<div class="item"><div class="item-label">Nationality</div><div class="item-value">{{ $employee->nationality }}</div></div>@endif
    </div>
    @if($employee->skills)
    <div class="section"><div class="section-title">Core skills</div>
        @php($skillItems = preg_split('/[\r\n,]+/', $employee->skills, -1, PREG_SPLIT_NO_EMPTY))
        <ul class="skills">@foreach($skillItems as $skill)<li>{{ trim($skill) }}</li>@endforeach</ul>
    </div>
    @endif
    <div class="confidential">Profile generated by Manpower Workforce Management. Identity and medical details remain in the secured internal employee record.</div>
</div>
<div class="main">
    <div class="section"><div class="section-title">Professional profile</div>
        @if($employee->professional_summary)<p class="summary">{{ $employee->professional_summary }}</p>@else<p class="empty">Professional summary not provided.</p>@endif
    </div>
    @if($employee->education)
    <div class="section"><div class="section-title">Education & qualifications</div><p class="education">{{ $employee->education }}</p></div>
    @endif
    <div class="section"><div class="section-title">Professional experience</div>
    @forelse($employee->previous_experience ?? [] as $experience)
        <div class="experience-item">
            <div class="experience-role">{{ $experience['position'] ?? 'Previous role' }}</div>
            <div class="experience-date">@if(!empty($experience['start_date'])){{ \Carbon\Carbon::parse($experience['start_date'])->format('M Y') }} - {{ !empty($experience['end_date']) ? \Carbon\Carbon::parse($experience['end_date'])->format('M Y') : 'Present' }}@else{{ $experience['duration'] ?? '' }}@endif</div>
            @if(!empty($experience['company_name']) || !empty($experience['location']))<div class="experience-company">{{ $experience['company_name'] ?? '' }}@if(!empty($experience['company_name']) && !empty($experience['location'])) <span class="experience-location">|</span> @endif<span class="experience-location">{{ $experience['location'] ?? '' }}</span></div>@endif
            @if(!empty($experience['responsibilities']))<div class="responsibilities">{{ $experience['responsibilities'] }}</div>@endif
        </div>
    @empty<p class="empty">Previous professional experience has not been provided.</p>@endforelse
    </div>
    @if($employee->document_path)<div class="document-note"><strong>Supporting documents</strong><br>The employee's uploaded supporting document follows this CV.</div>@endif
</div>
</div>
</body>
</html>
