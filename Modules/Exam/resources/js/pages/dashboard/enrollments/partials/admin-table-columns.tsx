import DeleteModal from '@/components/inertia/delete-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { destroy as destroyExamEnrollment } from '@/routes/exam-enrollments';
import { destroy as destroyCourseEnrollment } from '@/routes/exam-enrollments';
import type { ColumnDef } from '@tanstack/react-table';
import { Lock, ShieldCheck, Trash2, Unlock } from 'lucide-react';
import { router } from '@inertiajs/react';

const GovernanceActions = ({ enrollment }: { enrollment: ExamEnrollment }) => {
   const updateGovernance = (changes: Partial<ExamEnrollment>) => {
      router.patch(
         `/dashboard/exams/exam/enrollments/${enrollment.id}/governance`,
         {
            access_granted: changes.access_granted ?? enrollment.access_granted,
            payment_status: changes.payment_status ?? enrollment.payment_status,
            amount_paid: changes.amount_paid ?? enrollment.amount_paid,
            results_locked: changes.results_locked ?? enrollment.results_locked,
         },
         { preserveScroll: true },
      );
   };

   const getPaymentBadge = (status: string) => {
      switch (status) {
         case 'paid':
            return <Badge className="bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20">Paid</Badge>;
         case 'partial':
            return <Badge className="bg-amber-500/10 text-amber-600 hover:bg-amber-500/20">Partial</Badge>;
         case 'blocked':
            return <Badge className="bg-rose-500/10 text-rose-600 hover:bg-rose-500/20">Blocked</Badge>;
         default:
            return <Badge className="bg-gray-500/10 text-gray-600 hover:bg-gray-500/20">Pending</Badge>;
      }
   };

   return (
      <div className="flex items-center gap-2">
         {getPaymentBadge(enrollment.payment_status || 'pending')}

         <select
            value={enrollment.payment_status || 'pending'}
            onChange={(e) => updateGovernance({ payment_status: e.target.value })}
            className="h-8 rounded-md border border-input bg-background px-2 text-xs"
         >
            <option value="pending">Pending Fee</option>
            <option value="partial">Partial Fee</option>
            <option value="paid">Paid Full</option>
            <option value="blocked">Blocked</option>
         </select>

         <Button
            type="button"
            size="sm"
            variant={enrollment.access_granted ? 'default' : 'outline'}
            title={enrollment.access_granted ? 'Access Granted (Click to Revoke)' : 'Access Revoked (Click to Grant)'}
            onClick={() => updateGovernance({ access_granted: !enrollment.access_granted })}
            className={enrollment.access_granted ? 'bg-emerald-600 hover:bg-emerald-700' : 'text-rose-600'}
         >
            {enrollment.access_granted ? <ShieldCheck className="h-4 w-4" /> : <Unlock className="h-4 w-4" />}
         </Button>

         <Button
            type="button"
            size="sm"
            variant="outline"
            title={enrollment.results_locked ? 'Results Locked (Click to Unlock)' : 'Results Unlocked (Click to Lock)'}
            onClick={() => updateGovernance({ results_locked: !enrollment.results_locked })}
            className={enrollment.results_locked ? 'text-amber-600 border-amber-300' : 'text-muted-foreground'}
         >
            {enrollment.results_locked ? <Lock className="h-4 w-4 text-amber-600" /> : <Unlock className="h-4 w-4" />}
         </Button>
      </div>
   );
};

const AdminTableColumn = (
   enrollmentType: 'course' | 'exam',
   translate: LanguageTranslations,
   deleteRoute: string,
): ColumnDef<CourseEnrollment | ExamEnrollment>[] => {
   const { table } = translate;

   return [
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
            // Convert to a readable date format
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
      {
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
      },
   ];
};

export default AdminTableColumn;
