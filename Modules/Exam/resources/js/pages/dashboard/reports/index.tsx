import { Head, router } from '@inertiajs/react';
import type { SortingState } from '@tanstack/react-table';
import {
   flexRender,
   getCoreRowModel,
   getFilteredRowModel,
   getSortedRowModel,
   useReactTable,
} from '@tanstack/react-table';
import {
   AlertOctagon,
   BookOpen,
   CheckCircle2,
   Clock,
   FileSpreadsheet,
   Filter,
   Lock,
   Search,
   ShieldAlert,
   Users,
} from 'lucide-react';
import * as React from 'react';
import type { ReactNode } from 'react';
import Breadcrumbs from '@/components/breadcrumbs';
import TableFooter from '@/components/table/table-footer';
import TableHeader from '@/components/table/table-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
   Select,
   SelectContent,
   SelectItem,
   SelectTrigger,
   SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import DashboardLayout from '@/layouts/dashboard/layout';
import AdminTableColumn from '../enrollments/partials/admin-table-columns';

interface ExamReportsProps extends SharedData {
   enrollments: Pagination<ExamEnrollment>;
   stats: {
      total_enrollments: number;
      paid_full: number;
      pending_payment: number;
      access_revoked: number;
      results_locked: number;
      offline_graded: number;
   };
   exams: Array<{ id: number; title: string; exam_mode?: string; total_marks?: number }>;
   filters: {
      search?: string;
      exam_id?: string;
      payment_status?: string;
      results_locked?: string;
   };
}

const ExamReports = (props: ExamReportsProps) => {
   const { enrollments, stats, exams, filters, translate } = props;
   const [sorting, setSorting] = React.useState<SortingState>([]);
   const [search, setSearch] = React.useState(filters.search || '');
   const [examId, setExamId] = React.useState(filters.exam_id || 'all');
   const [paymentStatus, setPaymentStatus] = React.useState(filters.payment_status || 'all');
   const [resultsLocked, setResultsLocked] = React.useState(filters.results_locked || 'all');

   const handleFilter = () => {
      router.get(
         '/dashboard/exams/exam/reports',
         {
            search: search || undefined,
            exam_id: examId !== 'all' ? examId : undefined,
            payment_status: paymentStatus !== 'all' ? paymentStatus : undefined,
            results_locked: resultsLocked !== 'all' ? resultsLocked : undefined,
         },
         { preserveState: true, replace: true },
      );
   };

   const handleReset = () => {
      setSearch('');
      setExamId('all');
      setPaymentStatus('all');
      setResultsLocked('all');
      router.get('/dashboard/exams/exam/reports', {}, { preserveState: true, replace: true });
   };

   const table = useReactTable({
      data: enrollments.data,
      columns: AdminTableColumn('exam', translate, 'exam-enrollments.destroy'),
      onSortingChange: setSorting,
      getCoreRowModel: getCoreRowModel(),
      getSortedRowModel: getSortedRowModel(),
      getFilteredRowModel: getFilteredRowModel(),
      state: { sorting },
   });

   return (
      <>
         <Head title="Student Exam Reports & Governance" />

         <Breadcrumbs
            title="Student Reports & Governance"
            breadcrumbs={[
               { title: 'Dashboard', href: '/dashboard' },
               { title: 'Exams', href: '/dashboard/exams' },
               { title: 'Student Reports & Governance' },
            ]}
         />

         <div className="space-y-6">
            {/* Header Description */}
            <div className="rounded-xl border bg-card p-6 shadow-sm">
               <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                  <div>
                     <h2 className="text-2xl font-bold tracking-tight">Exam Governance & Physical Exam Reports</h2>
                     <p className="text-sm text-muted-foreground">
                        Manage student exam permissions, release/lock results, record physical exam scores, and oversee fee status.
                     </p>
                  </div>
                  <Badge variant="outline" className="w-fit px-3 py-1 text-sm font-medium">
                     <FileSpreadsheet className="mr-2 h-4 w-4 text-primary" /> Active System Governance
                  </Badge>
               </div>
            </div>

            {/* Key Metrics Cards Grid */}
            <div className="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
               <Card className="border-l-4 border-l-primary shadow-sm">
                  <CardHeader className="flex flex-row items-center justify-between pb-2 p-3 sm:p-4">
                     <CardTitle className="text-[10px] sm:text-xs font-medium uppercase text-muted-foreground">
                        Total Enrolled
                     </CardTitle>
                     <Users className="h-4 w-4 text-primary shrink-0" />
                  </CardHeader>
                  <CardContent className="p-3 pt-0 sm:p-4 sm:pt-0">
                     <div className="text-xl sm:text-2xl font-bold">{stats.total_enrollments}</div>
                     <p className="text-[11px] text-muted-foreground">Active students</p>
                  </CardContent>
               </Card>

               <Card className="border-l-4 border-l-emerald-500 shadow-sm">
                  <CardHeader className="flex flex-row items-center justify-between pb-2 p-3 sm:p-4">
                     <CardTitle className="text-[10px] sm:text-xs font-medium uppercase text-muted-foreground">
                        Paid Full
                     </CardTitle>
                     <CheckCircle2 className="h-4 w-4 text-emerald-500 shrink-0" />
                  </CardHeader>
                  <CardContent className="p-3 pt-0 sm:p-4 sm:pt-0">
                     <div className="text-xl sm:text-2xl font-bold text-emerald-600">{stats.paid_full}</div>
                     <p className="text-[11px] text-muted-foreground">Cleared fees</p>
                  </CardContent>
               </Card>

               <Card className="border-l-4 border-l-amber-500 shadow-sm">
                  <CardHeader className="flex flex-row items-center justify-between pb-2 p-3 sm:p-4">
                     <CardTitle className="text-[10px] sm:text-xs font-medium uppercase text-muted-foreground">
                        Pending Fee
                     </CardTitle>
                     <Clock className="h-4 w-4 text-amber-500 shrink-0" />
                  </CardHeader>
                  <CardContent className="p-3 pt-0 sm:p-4 sm:pt-0">
                     <div className="text-xl sm:text-2xl font-bold text-amber-600">{stats.pending_payment}</div>
                     <p className="text-[11px] text-muted-foreground">Awaiting clearance</p>
                  </CardContent>
               </Card>

               <Card className="border-l-4 border-l-rose-500 shadow-sm">
                  <CardHeader className="flex flex-row items-center justify-between pb-2 p-3 sm:p-4">
                     <CardTitle className="text-[10px] sm:text-xs font-medium uppercase text-muted-foreground">
                        Access Revoked
                     </CardTitle>
                     <ShieldAlert className="h-4 w-4 text-rose-500 shrink-0" />
                  </CardHeader>
                  <CardContent className="p-3 pt-0 sm:p-4 sm:pt-0">
                     <div className="text-xl sm:text-2xl font-bold text-rose-600">{stats.access_revoked}</div>
                     <p className="text-[11px] text-muted-foreground">Blocked students</p>
                  </CardContent>
               </Card>

               <Card className="border-l-4 border-l-purple-500 shadow-sm">
                  <CardHeader className="flex flex-row items-center justify-between pb-2 p-3 sm:p-4">
                     <CardTitle className="text-[10px] sm:text-xs font-medium uppercase text-muted-foreground">
                        Results Locked
                     </CardTitle>
                     <Lock className="h-4 w-4 text-purple-500 shrink-0" />
                  </CardHeader>
                  <CardContent className="p-3 pt-0 sm:p-4 sm:pt-0">
                     <div className="text-xl sm:text-2xl font-bold text-purple-600">{stats.results_locked}</div>
                     <p className="text-[11px] text-muted-foreground">Restricted scores</p>
                  </CardContent>
               </Card>

               <Card className="border-l-4 border-l-blue-500 shadow-sm">
                  <CardHeader className="flex flex-row items-center justify-between pb-2 p-3 sm:p-4">
                     <CardTitle className="text-[10px] sm:text-xs font-medium uppercase text-muted-foreground">
                        Physical Exams
                     </CardTitle>
                     <BookOpen className="h-4 w-4 text-blue-500 shrink-0" />
                  </CardHeader>
                  <CardContent className="p-3 pt-0 sm:p-4 sm:pt-0">
                     <div className="text-xl sm:text-2xl font-bold text-blue-600">{stats.offline_graded}</div>
                     <p className="text-[11px] text-muted-foreground">Paper exams graded</p>
                  </CardContent>
               </Card>
            </div>

            {/* Filter Toolbar */}
            <Card className="shadow-sm">
               <CardContent className="p-3 sm:p-4">
                  <div className="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-5">
                     <div className="relative">
                        <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                           placeholder="Search student name or email..."
                           value={search}
                           onChange={(e) => setSearch(e.target.value)}
                           className="pl-9"
                           onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                        />
                     </div>

                     <Select value={examId} onValueChange={setExamId}>
                        <SelectTrigger>
                           <SelectValue placeholder="All Exams" />
                        </SelectTrigger>
                        <SelectContent>
                           <SelectItem value="all">All Exams</SelectItem>
                           {exams.map((ex) => (
                              <SelectItem key={ex.id} value={ex.id.toString()}>
                                 {ex.title} {ex.exam_mode === 'physical' ? '(Physical)' : ''}
                              </SelectItem>
                           ))}
                        </SelectContent>
                     </Select>

                     <Select value={paymentStatus} onValueChange={setPaymentStatus}>
                        <SelectTrigger>
                           <SelectValue placeholder="Payment Status" />
                        </SelectTrigger>
                        <SelectContent>
                           <SelectItem value="all">All Payment Statuses</SelectItem>
                           <SelectItem value="paid">Paid Full</SelectItem>
                           <SelectItem value="partial">Partial Payment</SelectItem>
                           <SelectItem value="pending">Pending Payment</SelectItem>
                           <SelectItem value="blocked">Blocked</SelectItem>
                        </SelectContent>
                     </Select>

                     <Select value={resultsLocked} onValueChange={setResultsLocked}>
                        <SelectTrigger>
                           <SelectValue placeholder="Results Lock Status" />
                        </SelectTrigger>
                        <SelectContent>
                           <SelectItem value="all">All Lock Statuses</SelectItem>
                           <SelectItem value="1">Results Locked</SelectItem>
                           <SelectItem value="0">Results Unlocked</SelectItem>
                        </SelectContent>
                     </Select>

                     <div className="flex gap-2">
                        <Button onClick={handleFilter} className="flex-1">
                           <Filter className="mr-2 h-4 w-4" /> Filter
                        </Button>
                        <Button variant="outline" onClick={handleReset}>
                           Reset
                        </Button>
                     </div>
                  </div>
               </CardContent>
            </Card>

            {/* Main Governance Table */}
            <Card className="shadow-sm">
               <CardContent className="p-0">
                  <Table className="border-y border-border">
                     <TableHeader table={table} />

                     <TableBody>
                        {table.getRowModel().rows?.length ? (
                           table.getRowModel().rows.map((row) => (
                              <TableRow key={row.id} data-state={row.getIsSelected() && 'selected'}>
                                 {row.getVisibleCells().map((cell) => (
                                    <TableCell key={cell.id}>
                                       {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                    </TableCell>
                                 ))}
                              </TableRow>
                           ))
                        ) : (
                           <TableRow>
                              <TableCell colSpan={table.getAllColumns().length} className="h-24 text-center">
                                 No student enrollment governance records found matching your filters.
                              </TableCell>
                           </TableRow>
                        )}
                     </TableBody>
                  </Table>

                  <TableFooter
                     className="p-4 sm:p-6"
                     routeName="exam-enrollments.reports"
                     paginationInfo={enrollments}
                     paginationKey="reports"
                  />
               </CardContent>
            </Card>
         </div>
      </>
   );
};

ExamReports.layout = (page: ReactNode) => <DashboardLayout children={page} />;

export default ExamReports;
