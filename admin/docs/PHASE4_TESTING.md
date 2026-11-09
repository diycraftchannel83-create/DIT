# Phase 4 — Staging test checklist

- [ ] Create a KRS for a student and semester.
- [ ] Add a second class to the same submitted KRS.
- [ ] Verify class must belong to selected semester.
- [ ] Approve KRS and verify further class additions are blocked.
- [ ] Reject a submitted KRS and verify it cannot be edited.
- [ ] Login as assigned lecturer and enter a score for an approved student.
- [ ] Verify lecturer cannot enter scores for a class assigned to another lecturer.
- [ ] Verify a student without approved KRS cannot receive a grade or attendance record.
- [ ] Verify grade mapping against the institution's approved grading policy before production.
- [ ] Publish a grade as academic admin.
- [ ] Verify KHS semester GPA uses published grades and credit-weighted calculation.
- [ ] Verify cumulative GPA uses all published grades and credit-weighted calculation.
- [ ] Record attendance, then update the same meeting and verify no duplicate row.
- [ ] Verify role access and CSRF rejection for all POST actions.
- [ ] Back up and restore the staging database.

No live ByetHost or production test has been performed.
