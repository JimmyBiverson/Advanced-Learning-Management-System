import DeleteModal from '@/components/inertia/delete-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
   Dialog,
   DialogContent,
   DialogDescription,
   DialogHeader,
   DialogTitle,
   DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy as destroyExamEnrollment } from '@/routes/exam-enrollments';
import { destroy as destroyCourseEnrollment } from '@/routes/exam-enrollments';
import type { ColumnDef } from '@tanstack/react-table';
import { FileText, Lock, ShieldCheck, Trash2, Unlock } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useState } from 'react';

const GovernanceModal = ({ enrollment }: { enrollment: ExamEnrollment }) => {
   const [open, setOpen] = useState(false);
   const [paymentStatus, setPaymentStatus] = useState(enrollment.payment_status || 'pending');
   const [amountPaid, setAmountPaid] = useState(enrollment.amount_paid || 0);
   const [accessGranted, setAccessGranted] = useState(enrollment.access_granted ?? true);
   const [resultsLocked, setResultsLocked] = useState(enrollment.results_locked ?? false);
   const [offlineMarks, setOfflineMarks] = useState(enrollment.offline_marks || 0);
   const [offlineRemarks, setOfflineRemarks] = useState(enrollment.offline_remarks || '');

   const saveGovernance = () => {
      router.patch(
         `/dashboard/exams/exam/enrollments/${enrollment.id}/governance`,
         {
            payment_status: paymentStatus,
            amount_paid: amountPaid,
            access_granted: accessGranted,
            results_locked: resultsLocked,
            offline_marks: offlineMarks,
            offline_remarks: offlineRemarks,
         },
         {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
         },
      );
   };

   const getPaymentBadge = (status: string) => {
      switch (status) {
         case 'paid':
            return <Badge className="bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20">Paid Full</Badge>;
         case 'partial':
            return <Badge className="bg-amber-500/10 text-amber-600 hover:bg-amber-500/20">Partial Fee</Badge>;
         case 'blocked':
            return <Badge className="bg-rose-500/10 text-rose-600 hover:bg-rose-500/20">Blocked</Badge>;
         default:
            return <Badge className="bg-gray-500/10 text-gray-600 hover:bg-gray-500/20">Pending Fee</Badge>;
      }
   };

   return (
      <div className="flex items-center gap-2">
         {getPaymentBadge(enrollment.payment_status || 'pending')}

         <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
               <Button size="sm" variant="outline" className="gap-1 text-xs font-medium cursor-pointer">
                  <FileText className="h-3.5 w-3.5" />
                  Student Report & Governance
               </Button>
            </DialogTrigger>
            <DialogContent className="max-w-xl">
               <DialogHeader>
                  <DialogTitle>Student Exam Report & Governance</DialogTitle>
                  <DialogDescription>
                     Manage student exam access, fee payment status, result locking, and offline exam marks entry.
                  </DialogDescription>
               </DialogHeader>

               <div className="space-y-4 py-2 text-sm">
                  {/* Student & Exam Details */}
                  <div className="rounded-lg border bg-muted/30 p-3 space-y-1">
                     <p className="font-semibold text-foreground">{enrollment.user?.name || 'Student'}</p>
                     <p className="text-xs text-muted-foreground">{enrollment.user?.email}</p>
                     <p className="text-xs font-medium text-primary mt-1">Exam: {enrollment.exam?.title || 'N/A'}</p>
                  </div>

                  {/* Fee Payment Governance */}
                  <div className="space-y-2">
                     <Label className="font-semibold">Fee Payment Governance</Label>
                     <div className="grid grid-cols-2 gap-3">
                        <div>
                           <Label className="text-xs">Payment Status</Label>
                           <select
                              value={paymentStatus}
                              onChange={(e) => setPaymentStatus(e.target.value)}
                              className="mt-1 w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                           >
                              <option value="pending">Pending Fee</option>
                              <option value="partial">Partial Fee</option>
                              <option value="paid">Paid Full</option>
                              <option value="blocked">Blocked</option>
                           </select>
                        </div>
                        <div>
                           <Label className="text-xs">Amount Paid ($)</Label>
                           <Input
                              type="number"
                              value={amountPaid}
                              onChange={(e) => setAmountPaid(Number(e.target.value))}
                              className="mt-1 h-9 text-xs"
                              placeholder="0.00"
                           />
                        </div>
                     </div>
                  </div>

                  {/* Permissions & Result Controls */}
                  <div className="space-y-2">
                     <Label className="font-semibold">Permissions & Access Controls</Label>
                     <div className="flex items-center gap-3">
                        <Button
                           type="button"
                           size="sm"
                           variant={accessGranted ? 'default' : 'outline'}
                           onClick={() => setAccessGranted(!accessGranted)}
                           className={accessGranted ? 'bg-emerald-600 hover:bg-emerald-700' : 'text-rose-600 border-rose-300'}
                        >
                           {accessGranted ? <ShieldCheck className="mr-1.5 h-4 w-4" /> : <Unlock className="mr-1.5 h-4 w-4" />}
                           {accessGranted ? 'Exam Access Granted' : 'Exam Access Revoked'}
                        </Button>

                        <Button
                           type="button"
                           size="sm"
                           variant="outline"
                           onClick={() => setResultsLocked(!resultsLocked)}
                           className={resultsLocked ? 'text-amber-600 border-amber-300' : 'text-muted-foreground'}
                        >
                           {resultsLocked ? <Lock className="mr-1.5 h-4 w-4 text-amber-600" /> : <Unlock className="mr-1.5 h-4 w-4" />}
                           {resultsLocked ? 'Results Locked' : 'Results Unlocked'}
                        </Button>
                     </div>
                  </div>

                  {/* Physical / Offline Marks Entry */}
                  <div className="space-y-2 pt-2 border-t">
                     <Label className="font-semibold">Physical / Offline Exam Marks Entry</Label>
                     <div className="grid grid-cols-2 gap-3">
                        <div>
                           <Label className="text-xs">Offline Score / Marks</Label>
                           <Input
                              type="number"
                              value={offlineMarks}
                              onChange={(e) => setOfflineMarks(Number(e.target.value))}
                              className="mt-1 h-9 text-xs"
                              placeholder="e.g. 85"
                           />
                        </div>
                        <div>
                           <Label className="text-xs">Remarks / Grade Note</Label>
                           <Input
                              type="text"
                              value={offlineRemarks}
                              onChange={(e) => setOfflineRemarks(e.target.value)}
                              className="mt-1 h-9 text-xs"
                              placeholder="e.g. Passed with Distinction"
                           />
                        </div>
                     </div>
                  </div>
               </div>

               <div className="flex justify-end gap-2 pt-3 border-t">
                  <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                     Cancel
                  </Button>
                  <Button type="button" onClick={saveGovernance} className="bg-primary text-primary-foreground">
                     Save Report & Governance
                  </Button>
               </div>
            </DialogContent>
         </Dialog>
      </div>
   );
};

const AdminTableColumn = (
   enrollmentType: 'course' | 'exam',
   translate: LanguageTranslations,
   deleteRoute: string,
): ColumnDef<CourseEnrollment | ExamEnrollment>[] => {
   const { table } = translate;

   const columns: ColumnDef<CourseEnrollment | ExamEnrollment>[] = [
      {
         id: 'index',
         header: () => <div className="pl-4">#</div>,
         cell: ({ row }) => (
            <div className="w-4 pl-4 text-center font-medium">
               {row.index + 1}
            </div>
         ),
      },
      {
         id: 'name',
         header: table.name,
         cell: ({ row }) => {
            const user = row.original.user;

            return (
               <div className="flex items-center gap-3">
                  <div className="h-12 w-12 overflow-hidden rounded-full bg-muted">
                     {user.photo ? (
                        <img
                           src={user.photo}
                           alt={user.name}
                           className="h-full w-full object-cover"
                        />
                     ) : (
                        <div className="flex h-full w-full items-center justify-center bg-gray-200 text-gray-500">
                           <span className="text-lg">
                              {table.img_placeholder}
                           </span>
                        </div>
                     )}
                  </div>
                  <div>
                     <p className="font-medium">{user.name}</p>
                     <p className="text-sm text-muted-foreground">
                        {user.email}
                     </p>
                  </div>
               </div>
            );
         },
      },
      {
         id: 'enrolled_course',
         header: () =>
            enrollmentType === 'course'
               ? table.enrolled_course
               : 'Enrolled Exam',
         cell: ({ row }) => {
            const exam = row.original as ExamEnrollment;
            const course = row.original as CourseEnrollment;

            return (
               <div className="max-w-md">
                  <p className="line-clamp-1">
                     {enrollmentType === 'course'
                        ? course.course.title
                        : exam.exam.title}
                  </p>
               </div>
            );
         },
      },
      {
         id: 'enrolled_date',
         header: table.enrolled_date,
         cell: ({ row }) => {
            const date = new Date(row.original.entry_date);
            const formattedDate = date.toLocaleDateString('en-US', {
               month: 'long',
               day: '2-digit',
               year: 'numeric',
            });

            return <div>{formattedDate}</div>;
         },
      },
      {
         id: 'expiry_date',
         header: table.expiry_date,
         cell: ({ row }) => {
            if (!row.original.expiry_date) {
               return (
                  <Badge className="bg-green-100 text-green-800 hover:bg-green-100">
                     {table.lifetime_access}
                  </Badge>
               );
            }

            const date = new Date(row.original.expiry_date);
            const formattedDate = date.toLocaleDateString('en-US', {
               month: 'long',
               day: '2-digit',
               year: 'numeric',
            });

            return <div>{formattedDate}</div>;
         },
      },
   ];

   if (enrollmentType === 'exam') {
      columns.push({
         id: 'governance',
         header: 'Report & Governance',
         cell: ({ row }) => <GovernanceModal enrollment={row.original as ExamEnrollment} />,
      });
   }

   columns.push({
      id: 'actions',
      header: () => <div className="pr-4 text-end">{table.action}</div>,
      cell: ({ row }) => {
         return (
            <div className="flex justify-end pr-4">
               <DeleteModal
                  routePath={
                     deleteRoute === 'exam-enrollments.destroy'
                        ? destroyExamEnrollment.url(row.original.id)
                        : destroyCourseEnrollment.url(row.original.id)
                  }
                  actionComponent={
                     <Button
                        size="icon"
                        variant="ghost"
                        className="rounded-full text-destructive hover:bg-destructive/10 hover:text-destructive"
                     >
                        <Trash2 />
                     </Button>
                  }
               />
            </div>
         );
      },
   });

   return columns;
};

export default AdminTableColumn;
