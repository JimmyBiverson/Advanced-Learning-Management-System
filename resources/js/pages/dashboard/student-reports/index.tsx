import { Head } from '@inertiajs/react';
import {
   BarChart3,
   BookOpen,
   CheckCircle2,
   ClipboardCheck,
} from 'lucide-react';
import type { ReactNode } from 'react';
import Breadcrumbs from '@/components/breadcrumbs';
import TableFilter from '@/components/table/table-filter';
import TableFooter from '@/components/table/table-footer';
import { Card } from '@/components/ui/card';
import {
   Table,
   TableBody,
   TableCell,
   TableHead,
   TableHeader,
   TableRow,
} from '@/components/ui/table';
import DashboardLayout from '@/layouts/dashboard/layout';

type StudentReport = User & {
   enrollments_count: number;
   exam_attempts_count: number;
   passed_attempts_count: number;
};

interface Props extends SharedData {
   students: Pagination<StudentReport>;
   summary: {
      students: number;
      activeStudents: number;
      enrollments: number;
      passedAttempts: number;
   };
}

const Index = ({ students, summary }: Props) => (
   <>
      <Head title="Student Reports" />
      <Breadcrumbs
         title="Student Reports"
         breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Student Reports' },
         ]}
         className="mb-4"
      />
      <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
         <SummaryCard
            label="Students"
            value={summary.students}
            icon={<BarChart3 className="h-5 w-5 text-blue-600" />}
         />
         <SummaryCard
            label="Active students"
            value={summary.activeStudents}
            icon={<CheckCircle2 className="h-5 w-5 text-emerald-600" />}
         />
         <SummaryCard
            label="Course enrollments"
            value={summary.enrollments}
            icon={<BookOpen className="h-5 w-5 text-amber-600" />}
         />
         <SummaryCard
            label="Passed exam attempts"
            value={summary.passedAttempts}
            icon={<ClipboardCheck className="h-5 w-5 text-violet-600" />}
         />
      </div>
      <Card>
         <TableFilter
            data={students}
            title="Student report"
            globalSearch
            tablePageSizes={[15, 30, 50]}
            routeName="student-reports.index"
            filterKey="students"
         />
         <Table className="border-y border-border">
            <TableHeader>
               <TableRow>
                  <TableHead className="px-6">Student</TableHead>
                  <TableHead>Account</TableHead>
                  <TableHead>Enrollments</TableHead>
                  <TableHead>Exam attempts</TableHead>
                  <TableHead>Passed</TableHead>
               </TableRow>
            </TableHeader>
            <TableBody>
               {students.data.map((student) => (
                  <TableRow key={student.id}>
                     <TableCell className="px-6 font-medium">
                        {student.name}
                     </TableCell>
                     <TableCell>
                        <div>{student.email}</div>
                        <div className="text-xs text-muted-foreground">
                           {student.status === 1 ? 'Active' : 'Inactive'}
                        </div>
                     </TableCell>
                     <TableCell>{student.enrollments_count}</TableCell>
                     <TableCell>{student.exam_attempts_count}</TableCell>
                     <TableCell>{student.passed_attempts_count}</TableCell>
                  </TableRow>
               ))}
               {students.data.length === 0 && (
                  <TableRow>
                     <TableCell colSpan={5} className="h-24 text-center">
                        No students found.
                     </TableCell>
                  </TableRow>
               )}
            </TableBody>
         </Table>
         <TableFooter
            className="border-none p-5 sm:p-6"
            routeName="student-reports.index"
            paginationInfo={students}
            paginationKey="students"
         />
      </Card>
   </>
);

const SummaryCard = ({
   label,
   value,
   icon,
}: {
   label: string;
   value: number;
   icon: ReactNode;
}) => (
   <Card className="flex items-center justify-between p-5">
      <div>
         <p className="text-sm text-muted-foreground">{label}</p>
         <p className="mt-1 text-2xl font-semibold">{value}</p>
      </div>
      <div className="rounded-full bg-muted p-3">{icon}</div>
   </Card>
);

Index.layout = (page: ReactNode) => <DashboardLayout children={page} />;

export default Index;
