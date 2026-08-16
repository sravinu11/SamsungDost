<?php

$QUAL_EXPR = "CASE
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) = '12TH' THEN '12th'
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) = '12TH+DIPLOMA' THEN '12th + Diploma'
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) = 'DIPLOMA' THEN 'Diploma'
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) IN ('UNDERGRADUATE','UG') THEN 'Under Graduate'
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) IN ('GRADUATE','GRAD') THEN 'Graduate'
    WHEN UPPER(REPLACE(TRIM(qualification),' ','')) IN ('POSTGRADUATE','PG') THEN 'Post Graduate'
    ELSE INITCAP(TRIM(qualification))
END";

$GENDER_EXPR = "CASE
    WHEN UPPER(LEFT(TRIM(gender),1)) = 'M' THEN 'Male'
    WHEN UPPER(LEFT(TRIM(gender),1)) = 'F' THEN 'Female'
    ELSE 'Unknown'
END";

/* hardcoded (not re-queried from information_schema every request) to avoid an extra round trip per page load */
$ALL_COLUMNS = [
    'sr_no', 'implementation_partner', 'vertical_type', 'business_type', 'candidate_unique_id',
    'sidh_batch_id', 'candidate_hiring_status', 'badge_status', 'store_owner_survey', 'can_id_ekyc',
    'oms_id', 'region', 'state_name', 'location', 'ho_id', 'candidate_name', 'contact_number',
    'gender', 'date_of_birth', 'age_in_years', 'fathers_name', 'qualification', 'email_id',
    't_shirt_size', 'ojt_welcome_letter', 'posh_training', 'assessment_status', 'assessment_completed',
    'result', 'certificate_status', 'lwd', 'total_lms_hours', 'ojt_start_date', 'ojt_end_date',
    'ojt_duration', 'nhit_test_number', 'nhit_test_yn', 'abm_approval', 'sales_aptitude_status',
    'sales_aptitude_training_interviewer', 'category_pc_npc_ot', 'channel_mbo_exc_dcm_star_dcm_ot',
    'outlet_type', 'asd', 'parent_code', 'child_code', 'store_id', 'store_name', 'store_address',
    'tl_name', 'tl_contact', 'ase_ho_id', 'zse_ho_id', 'aadhar_number', 'm1_survey', 'm2_survey',
    'm3_survey', 'm4_survey', 'm5_survey', 'working_status',
];

$DIMS = [
    'business'      => "TRIM(business_type)",
    'partner'       => "TRIM(implementation_partner)",
    'vertical'      => "TRIM(vertical_type)",
    'region'        => "INITCAP(TRIM(region))",
    'state'         => "INITCAP(TRIM(state_name))",
    'qualification' => $QUAL_EXPR,
];

/* Every dimension supports one or more values — the client sends them
   comma-separated (?business=MX,DA). A single value works the same way. */
function activeFilters(array $DIMS): array {
    $filters = [];
    foreach (array_keys($DIMS) as $dim) {
        if (!isset($_GET[$dim]) || $_GET[$dim] === '') continue;
        $vals = array_values(array_filter(array_map('trim', explode(',', $_GET[$dim])), fn($v) => $v !== ''));
        if ($vals) $filters[$dim] = $vals;
    }
    return $filters;
}

function whereClause(array $DIMS, array $filters, ?string $exclude): array {
    $conds = [];
    $params = [];
    foreach ($DIMS as $dim => $expr) {
        if ($dim === $exclude) continue;
        if (empty($filters[$dim])) continue;
        $placeholders = [];
        foreach ($filters[$dim] as $i => $val) {
            $key = ":f_{$dim}_{$i}";
            $placeholders[] = $key;
            $params[$key] = $val;
        }
        $conds[] = "$expr IN (" . implode(",", $placeholders) . ")";
    }
    $sql = $conds ? ("WHERE " . implode(" AND ", $conds)) : "";
    return [$sql, $params];
}
