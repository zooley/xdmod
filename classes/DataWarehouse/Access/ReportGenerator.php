<?php

namespace DataWarehouse\Access;

class ReportGenerator extends Common
{
    /* Properties of reports. See also the enum columns in the moddb.Reports
     * table  */
    const REPORT_ID_REGEX = '/^[0-9]+-(?(?=autogenerated)autogenerated-[a-z]+|[0-9\.]+)$/';
    const REPORT_DATE_REGEX =  '/^[0-9]{4}(-[0-9]{2}){2}$/';
    const REPORT_FORMATS_REGEX = '/^doc|pdf$/';
    const REPORT_SCHEDULE_REGEX = '/^Once|Daily|Weekly|Monthly|Quarterly|Semi-annually|Annually$/';
    const REPORT_DELIVERY_REGEX = '/^E-Mail$/';

    /* Patterns related to report charts */
    const REPORT_CHART_TYPE_REGEX = '/^chart_pool|volatile|report|cached$/';
    const REPORT_CHART_REF_REGEX = '/^[0-9]+(-(?(?=autogenerated)autogenerated-[a-z]+|[0-9\.]+))?;[0-9]+$/';
    const REPORT_CHART_DID_REGEX = '/^_d[0-9]+$/';

    /* the save_report controller use a custom data serialization for charts
     * that have been modified from the original report */
    const CHART_CACHEREF_REGEX = '/^([0-9]{4}(-[0-9]{2}){2};){2}(?(?=xd_report_volatile_)xd_report_volatile_[0-9]+;[0-9]+(_d[0-9]+)?|[0-9]+-(?(?=autogenerated)autogenerated-[a-z]+|[0-9\.]+);[0-9]+)$/';

    /* The report download endpoint retrieves the report data from a temporary
     * directory that is created dynamically based on the report_id
     */
    const REPORT_TMPDIR_REGEX = '/^([0-9]+-(autogenerated-[a-z]+|[0-9\.]+)|monthly_compliance_report)-[a-zA-Z0-9\.]+$/';

    /*
     * Character encoding used in the (user supplied) text contained in the
     * report. This must be consistent with the character set used in the
     * moddb.Report table.
     */
    const REPORT_CHAR_ENCODING = 'ISO-8859-1';
}
