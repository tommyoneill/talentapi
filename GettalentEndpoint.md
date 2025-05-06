# Get Talent Endpoint

### **Get a Talentget**https://api.avionte.com/front-office/v1/talent/{talentId}Returns details about the specified talent record.

**Path Params
talentId** string required
A talentId to retrieve detail about the specified talent record

**Query Params
includeResume** string
An indication of whether the resume should be retrieved as part of the request or not. Set this URL parameter to 'True' to retrieve the resume or 'False' to omit it.

**Headers
FrontOfficeTenantId** string
Front office ID that indicates the tenant for which the request is being made. Either Tenant or FrontOfficeTenantId is required.

**RequestId** string
Allows client-specified request correlation. Provided value must be a valid GUID.

**Tenant** string
A short code that indicates the tenant for which the request is being made. Either Tenant or FrontOfficeTenantId is required.

**Authorization** string required
Contains the caller's access token, in the form "Bearer {token}", without quotes.

### **Responses**

# **200** response

**Response body**
object
**id** int32

The identifier for the talent.

**firstName** string

The talent's first name.

**middleName** string

The talent's middle name.

**lastName** string

The talent's last name.

**homePhone** string

The talent's home phone.

**workPhone** string

The talent's work phone.

**mobilePhone** string

The talent's mobile phone.

**pageNumber** string

The talent's pager number.

**emailAddress** string

The talent's email address.

**emailAddress2** string

The talent's email address 2.

**taxIdNumber** string

The talent's Taxpayer Identification Number. It may be either the US SSN or the Canadian SIN.

**birthday** date-time

The talent's birthday.

**gender** string

The talent's gender.

**hireDate** date-time

The talent's date of hire with the staffing agency.

**residentAddress** Address object

The talent's home address.

- **street1** string - Street address, line 1.
- **street2** string - Street address, line 2.
- **city** string - City.
- **state_Province** string - Province, state, or region.
- **postalCode** string - ZIP/postal code.
- **country** string - Country.
- **county** string - County.
- **geoCode** string - Numerical coordinate. The value is generated based on the ZIP code, city, and state of the address.
- **schoolDistrictCode** string - School district.

**mailingAddress** Address object

The talent's mailing address.

- **street1** string - Street address, line 1.
- **street2** string - Street address, line 2.
- **city** string - City.
- **state_Province** string - Province, state, or region.
- **postalCode** string - ZIP/postal code.
- **country** string - Country.
- **county** string - County.
- **geoCode** string - Numerical coordinate. The value is generated based on the ZIP code, city, and state of the address.
- **schoolDistrictCode** string - School district.

**payrollAddress** Address object

The talent's payroll mailing address.

- **street1** string - Street address, line 1.
- **street2** string - Street address, line 2.
- **city** string - City.
- **state_Province** string - Province, state, or region.
- **postalCode** string - ZIP/postal code.
- **country** string - Country.
- **county** string - County.
- **geoCode** string - Numerical coordinate. The value is generated based on the ZIP code, city, and state of the address.
- **schoolDistrictCode** string - School district.

**addresses** array of objects

The talent's home address.

- **street1** string - Street address, line 1.
- **street2** string - Street address, line 2.
- **city** string - City.
- **state_Province** string - Province, state, or region.
- **postalCode** string - ZIP/postal code.
- **country** string - Country.
- **county** string - County.
- **geoCode** string - Numerical coordinate. The value is generated based on the ZIP code, city, and state of the address.
- **schoolDistrictCode** string - School district.

**status** string

The talent's status.

**filingStatus** FormW4FilingStatus

Tax filing status. Filing statuses available for selection for Form W-4:

- None
- Single
- Married
- MarriedWithHigherRates

**federalAllowances** int32

Federal withholding exemptions.

**stateAllowances** int32

State withholding exemptions.

**additionalFederalWithholding** number

Additional amount of money that is withheld from the talent's each paycheck based on the talent's request.

**i9ValidatedDate** date-time

The date the talent's I-9 document was verified.

**frontOfficeId** int32

The talent's home office ID.

**latestActivityDate** date-time

The date of the talent's latest activity.

**latestActivityName** string

The talent's latest activity type.

**link** string

URL to the talent profile.

**race** string

The talent's race.

**disability** string

The talent's disability status.

**veteranStatus** string

The talent's veteran status.

**emailOptOut** boolean

An indication of whether the talent has opted out of receiving emails from Avionté BOLD or not.

**isArchived** boolean

An indication of whether the talent record is archived or not.

**placementStatus** string

The talent's current placement status.

**representativeUser** int32

The ID of the representative who is working with the talent.

**w2Consent** boolean

An indication of whether the talent has consented to receive the W-2 form electronically or not.

**electronic1095CConsent** boolean

An indication of whether the talent has consented to receive the 1095-C form electronically or not.

**referredBy** string

The ID of the user who the talent was referred by.

**availabilityDate** date-time

The date when the talent is available for work.

**statusId** int32

The talent's status ID.

**officeName** string

The name of the talent's home office.

**officeDivision** string

The division of the talent's home office.

**enteredByUserId** int32

The ID of the user who entered the talent record.

**enteredByUser** string

The email of the user who entered the talent record.

**representativeUserEmail** string

The email of talent's representative user.

**createdDate** date-time

The date the talent profile was created.

**lastUpdatedDate** date-time

The date of the most recent update to the talent profile.

**latestWork** string

The most recent employment of the talent (company and position).

**lastContacted** date-time

The date the talent was last contacted via the Send Email option on their profile.

**flag** string

An indication of whether the talent profile is flagged. If flagged, the value is specified as Green, Yellow or Red or "" for cases when an empty value is selected.

**origin** string

The partner or vendor that originated the request.

**originRecordId** string

The identifier for the talent that can be used to link the record within the originating system.

**electronic1099Consent** boolean

An indication of whether the talent has consented to the electronic delivery of forms or not.

**textConsent** string

Indicates if a talent has consented to receive text messages. Acceptable Values are "Opt Out", "Opt In", and "No Response"

**talentResume** Resume

Resume of the talent.

**rehireDate** date-time

The talent's date of rehire with the staffing agency.

**terminationDate** date-time

The talent's date of termination with the staffing agency.

**employmentTypeId** int32

The identifier for the talent's employment type which can represent a custom W2, 1099, or C2C value.

**employmentType** string

The type of employment associated to the talent's custom employment type value which can be W2, 1099, or C2C.

**employmentTypeName** string

The talent's custom employment type value.

**400**

Bad Request

**401**

Unauthorized

**403**

Access Denied

**406**

Unsupported format(s) indicated by Accept header

**415**

Unsupported format(s) indicated by the Content-Type header

**429**

Too many requests

**500**

An unexpected error occurred

**502**

The request could not be completed because an error occurred in an upstream service