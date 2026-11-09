# Phase 3 test checklist

Use a staging database only. Never test using live student data.

- [ ] Login as super_admin.
- [ ] Login as admin_akademik.
- [ ] Verify ketua_prodi and ketua_stit cannot submit academic write forms.
- [ ] Create a student with a unique NIM and username.
- [ ] Confirm both users and students rows exist.
- [ ] Try duplicate NIM and confirm transaction rollback leaves no new users row.
- [ ] Create a lecturer and verify lecturer profile.
- [ ] Create a course under an existing study program.
- [ ] Create a course class for an existing semester.
- [ ] Add a schedule with valid time range.
- [ ] Try end time earlier than start time and confirm rejection.
- [ ] Create an admin_akademik or admin_keuangan account and test role access.
- [ ] Confirm passwords are not returned in any listing.
- [ ] Verify all forms reject missing/invalid CSRF tokens.
- [ ] Test database backup and restore before production rollout.

Deployment and end-to-end testing on ByetHost have not yet been completed.
